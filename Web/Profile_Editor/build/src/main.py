from flask import Flask, session, render_template, request, redirect
import os
from os.path import commonpath, abspath, exists

app = Flask(__name__)

users = {}
app.config['TEMPLATES_AUTO_RELOAD'] = True
app.secret_key = os.urandom(24)

@app.route('/', methods=['GET'])
def home():
    return redirect('/register')

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form.get('username')
        password = request.form.get('password')

        if username in users and users[username] == password:
            session['username'] = username
            return redirect('/profile')
        else:
            return render_template('error.html', msg='Invalid username or password!', return_to='/login')

    return render_template('login.html')

@app.route('/register', methods=['GET', 'POST'])
def register():
    if request.method == 'POST':
        username = request.form.get('username')
        if username in users:
            return render_template('error.html', msg='Username already taken!', return_to='/register')
        users[username] = request.form.get('password')
        return redirect('/login')
    return render_template('register.html')

@app.route('/profile', methods=['GET', 'POST'])
def profile():
    if not session.get('username'):
        return redirect('/login')
    
    profiles_file = 'profile/' + session.get('username')
    profiles_file = profiles_file if profiles_file.endswith('.html') else profiles_file + '.html'

    if commonpath((app.root_path, abspath(profiles_file))) != app.root_path:
        return render_template('error.html', msg='Error processing profile file!', return_to='/profile')

    if request.method == 'POST':
        with open(profiles_file, 'w') as f:
            f.write(request.form.get('profile'))
        return redirect('/profile')
    
    profile=''
    if exists(profiles_file):
        with open(profiles_file, 'r') as f:
            profile = f.read()

    return render_template('profile.html', username=session.get('username'), profile=profile)

@app.route('/show_profile', methods=['GET', 'POST'])
def show_profile():
    if not session.get('username'):
        return redirect('/login')
    
    profiles_file = 'profile/' + session.get('username')

    if commonpath((app.root_path, abspath(profiles_file))) != app.root_path:
        return render_template('error.html', msg='Error processing profile file!', return_to='/profile')

    profile = ''
    if exists(profiles_file):
        with open(profiles_file, 'r') as f:
            profile = f.read()

    return render_template('show_profile.html', username=session.get('username'), profile=profile)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)