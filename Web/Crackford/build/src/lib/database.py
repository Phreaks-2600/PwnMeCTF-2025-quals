from flask_sqlalchemy import SQLAlchemy
from sqlalchemy import text
from flask_login import UserMixin
from werkzeug.security import generate_password_hash, check_password_hash
from faker import Faker
from random import randint
from lib.utils import encode

db = SQLAlchemy()


class User(UserMixin, db.Model):
    id = db.Column(db.Integer, primary_key=True, autoincrement=True)
    username = db.Column(db.String(100), unique=False, nullable=False)
    mail = db.Column(db.String(100), unique=True, nullable=False)
    password = db.Column(db.String(), nullable=False)
    role = db.Column(db.String(15), nullable=False, default="guest")

    def __init__(self, username, mail, password, role):
        self.username = username
        self.mail = mail
        self.password = generate_password_hash(password)
        self.role = role

    def check_password(self, password):
        return check_password_hash(self.password, password)

    def set_password(self, password):
        self.password = generate_password_hash(password)

    def get_reset_password_link(self):
        return encode(f"{self.mail}|{self.id}|PWNME CTF")


def fill_database():
    statement = text(f"DELETE FROM {User.__tablename__}")
    db.session.execute(statement)
    id_admin = randint(3, 37)
    for i in range(0, 41):
        user = create_user_with_id(i * 4)
        if i == 2:
            user.role = "support"
        if i == id_admin:
            user.role = "top_super_user"
        db.session.add(user)

    db.session.commit()


fake = Faker()


def create_user_with_id(id):
    name = fake.name()
    mail = f'{name.lower().replace(" ", "_") + str(randint(10, 200))}@pwnme.fr'

    password = fake.password(
        length=25, special_chars=True, digits=True, upper_case=True, lower_case=True
    )
    user = User(name, mail, password, "guest")
    user.id = id
    return user
