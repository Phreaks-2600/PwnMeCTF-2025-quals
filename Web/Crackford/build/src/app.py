from flask import Flask
from secrets import token_hex
from lib.database import fill_database, db


app = Flask(__name__)
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///users.db'
app.config['SECRET_KEY'] = token_hex(32)

db.init_app(app)
with app.app_context():
    db.create_all()
    fill_database()

from lib.api import *

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8085, threaded=True)