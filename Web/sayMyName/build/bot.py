from selenium import webdriver
from selenium.webdriver.firefox.options import Options
from selenium.webdriver.support.ui import WebDriverWait

def visit_report(url, X_Admin_Token):
    options = Options()
    options.add_argument('--headless')
    options.set_preference("security.sandbox.content.level", 0)
    options.set_preference("security.warn_submit_secure_to_insecure", False)
    browser = webdriver.Firefox(options=options)

    try:
        browser.get('http://127.0.0.1:5000/')
        cookie = {
            'name':'X-Admin-Token', 
            'value': X_Admin_Token,
            'secure': False,
            'httpOnly': False,
            'expiry': None
        }
        browser.add_cookie(cookie)
        print(f'Visit : {url}')
        browser.get(url)
        WebDriverWait(browser, 5).until(lambda r: r.execute_script('return document.readyState') == 'complete')
    except Exception as e:
        print(f"Error: {e}")
    finally:
        browser.quit()
