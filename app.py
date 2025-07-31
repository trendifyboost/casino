import os
import logging
from flask import Flask
from flask_sqlalchemy import SQLAlchemy
from sqlalchemy.orm import DeclarativeBase
from werkzeug.middleware.proxy_fix import ProxyFix

# Set up logging
logging.basicConfig(level=logging.DEBUG)

class Base(DeclarativeBase):
    pass

db = SQLAlchemy(model_class=Base)

# Create the app
app = Flask(__name__)
app.secret_key = os.environ.get("SESSION_SECRET", "casino_secret_key_2025")
app.wsgi_app = ProxyFix(app.wsgi_app, x_proto=1, x_host=1)

# Configure the database
database_url = os.environ.get("DATABASE_URL", "sqlite:///casino.db")

# MySQL configuration for shared hosting
if database_url.startswith('mysql'):
    # Use PyMySQL as the driver for MySQL
    if 'pymysql' not in database_url:
        database_url = database_url.replace('mysql://', 'mysql+pymysql://')
    app.config["SQLALCHEMY_ENGINE_OPTIONS"] = {
        "pool_recycle": 300,
        "pool_pre_ping": True,
        "pool_timeout": 20,
        "pool_size": 10,
        "max_overflow": 0
    }
else:
    # PostgreSQL or SQLite configuration
    app.config["SQLALCHEMY_ENGINE_OPTIONS"] = {
        "pool_recycle": 300,
        "pool_pre_ping": True,
    }

app.config["SQLALCHEMY_DATABASE_URI"] = database_url
app.config["UPLOAD_FOLDER"] = "uploads"
app.config["MAX_CONTENT_LENGTH"] = 16 * 1024 * 1024  # 16MB max file size

# Initialize the app with the extension
db.init_app(app)

# Create upload directory if it doesn't exist
os.makedirs(app.config["UPLOAD_FOLDER"], exist_ok=True)

with app.app_context():
    # Import models and routes
    import models
    from routes import auth, user, admin, main
    
    # Register blueprints
    app.register_blueprint(auth.bp)
    app.register_blueprint(user.bp)
    app.register_blueprint(admin.bp)
    app.register_blueprint(main.bp)
    
    # Create all tables
    db.create_all()
    
    # Create default admin user
    from models import Admin
    from werkzeug.security import generate_password_hash
    
    admin_user = Admin.query.filter_by(username='admin').first()
    if not admin_user:
        admin_user = Admin(
            username='admin',
            password_hash=generate_password_hash('admin'),
            role='super_admin'
        )
        db.session.add(admin_user)
        db.session.commit()
        print("Default admin user created (admin/admin)")

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
