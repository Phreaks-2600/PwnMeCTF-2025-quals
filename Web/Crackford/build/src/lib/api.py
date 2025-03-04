from app import app
from flask import request, session, render_template, redirect, url_for
from lib.database import User, db
from secrets import token_hex
from lib.utils import decode, is_email
from sqlalchemy import text
import os

@app.route("/")
def index():
    if "authenticated" not in session:
        session["authenticated"] = False
    if session["authenticated"]:
        user = User.query.filter_by(role='support').first()
        return render_template(
            "panel.html",
            flag=os.environ.get('FLAG'),
            showFlag=session["role"] == "admin",
            support_user=user,
            current_user=User.query.filter_by(id=session["id"]).first()
        )
    return render_template("index.html")


@app.route("/logout", methods=["GET"])
def logout():
    session["authenticated"] = False
    session["status"] = None
    return redirect(url_for("index"))


@app.route("/change-password", methods=["GET"])
def change_password():
    try:
        decoded = decode(request.args["h"]).split("|")
        email = decoded[0].strip()
        user_id = decoded[1].strip()
        if is_email(email):
            query = f"SELECT mail FROM {User.__tablename__} WHERE id='{user_id}' AND mail='{email}'"
            user = db.session.execute(text(query)).first()
        if user is None:
            return {"message": "Error"}, 404
    except Exception as e:
        return {"message": str(e)}, 500

    return render_template(
        "change-password.html",
        email=user[0],
    )


@app.route("/api/change-password", methods=["POST"])
def set_password():
    try:
        password = request.json.get("password")
        decoded = decode(request.args["h"]).split("|")
        email = decoded[0].strip()
        user_id = decoded[1].strip()
        user = User.query.filter_by(mail=email, id=user_id).first()
        if user is None:
            return {"message": "Error"}, 404
        user.set_password(password)
        db.session.commit()
    except Exception as e:
        print(e)
        return {"message": "Error"}, 500

    return {"message": "Password changed"}, 200


@app.route("/api/register", methods=["POST"])
def register():
    name = request.json.get("username")
    mail = request.json.get("email")
    user = User.query.filter_by(mail=mail).first()
    if user:
        return {
            "message": "User already exists",
        }, 401
    if not is_email(mail):
        return {
            "message": "Invalid email",
        }, 401
    password = token_hex(32)
    user = User(name, mail, password, "guest")
    db.session.add(user)
    db.session.commit()
    user = User.query.filter_by(mail=mail).first()
    return {
        "message": "Account created. You can set a new password",
        "changePasswordLink": user.get_reset_password_link(),
    }, 201


@app.route("/api/login", methods=["POST"])
def login():
    try:
        mail = request.json.get("email")
        password = request.json.get("password")
    except:
        return {"message": "Invalid request."}, 400
    user = User.query.filter_by(mail=mail).first()
    if user is None:
        return {"message": "User not found."}, 404
    if user.check_password(password):
        session["id"] = user.id
        session["authenticated"] = True
        session["role"] = user.role
        return {"message": "Login successful."}, 200
    else:
        return {"message": "Invalid credentials."}, 401

