from flask import Flask, render_template, request, Response, redirect, url_for
from bot import visit_report
from secrets import token_hex

X_Admin_Token = token_hex(16)

def run_cmd(): # I will do that later
    pass

def sanitize_input(input_string):
    input_string = input_string.replace('<', '')
    input_string = input_string.replace('>', '')
    input_string = input_string.replace('\'', '')
    input_string = input_string.replace('&', '')
    input_string = input_string.replace('"', '\\"')
    input_string = input_string.replace(':', '')
    return input_string

app = Flask(__name__)

@app.route('/admin', methods=['GET'])
def admin():
    if request.cookies.get('X-Admin-Token') != X_Admin_Token:
        return 'Access denied', 403
    
    prompt = request.args.get('prompt')
    return render_template('admin.html', cmd=f"{prompt if prompt else 'prompt$/>'}{run_cmd()}".format(run_cmd))

@app.route('/', methods=['GET'])
def index():
    return render_template('index.html')

@app.route('/your-name', methods=['POST'])
def your_name():
    if request.method == 'POST':
        name = request.form.get('name')
        return Response(render_template('your-name.html', name=sanitize_input(name)), content_type='text/html')
    
@app.route('/report', methods=['GET'])
def report():
    url = request.args.get('url')
    if url and (url.startswith('http://') or url.startswith('https://')):
        print(f'Visit : {url} | X-Admin-Token : {X_Admin_Token}')
        visit_report(url, X_Admin_Token)
    return redirect(url_for('index'))

app.run(debug=False, host='0.0.0.0')