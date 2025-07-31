from functools import wraps
from flask import session, redirect, url_for, flash
from models import User, Admin

def login_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'user_id' not in session:
            flash('Please login to access this page', 'error')
            return redirect(url_for('auth.login'))
        return f(*args, **kwargs)
    return decorated_function

def admin_login_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'admin_id' not in session:
            return redirect(url_for('admin.login'))
        return f(*args, **kwargs)
    return decorated_function

def get_current_user():
    if 'user_id' in session:
        return User.query.get(session['user_id'])
    return None

def get_current_admin():
    if 'admin_id' in session:
        return Admin.query.get(session['admin_id'])
    return None

def format_currency(amount, currency='USD'):
    """Format amount as currency"""
    return f"{currency} {amount:,.2f}"

def generate_pagination_info(pagination):
    """Generate pagination information for templates"""
    return {
        'has_prev': pagination.has_prev,
        'prev_num': pagination.prev_num,
        'has_next': pagination.has_next,
        'next_num': pagination.next_num,
        'page': pagination.page,
        'pages': pagination.pages,
        'total': pagination.total
    }
