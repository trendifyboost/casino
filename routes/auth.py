from flask import Blueprint, render_template, request, redirect, url_for, flash, session
from models import User, SiteSettings
from app import db
from utils.helpers import login_required
from datetime import datetime

bp = Blueprint('auth', __name__, url_prefix='/auth')

@bp.route('/register', methods=['GET', 'POST'])
def register():
    if request.method == 'POST':
        full_name = request.form.get('full_name')
        phone = request.form.get('phone')
        password = request.form.get('password')
        confirm_password = request.form.get('confirm_password')
        referral_code = request.form.get('referral_code')
        
        # Validation
        if not all([full_name, phone, password, confirm_password]):
            flash('All fields are required', 'error')
            return render_template('auth/register.html')
        
        if password != confirm_password:
            flash('Passwords do not match', 'error')
            return render_template('auth/register.html')
        
        if User.query.filter_by(phone=phone).first():
            flash('Phone number already registered', 'error')
            return render_template('auth/register.html')
        
        # Create user
        user = User(
            full_name=full_name,
            phone=phone
        )
        user.set_password(password)
        
        # Handle referral
        if referral_code:
            referrer = User.query.filter_by(referral_code=referral_code).first()
            if referrer:
                user.referred_by = referrer.id
        
        db.session.add(user)
        db.session.commit()
        
        # Add signup bonus
        settings = SiteSettings.query.first()
        if settings and settings.signup_bonus > 0:
            user.bonus_balance += settings.signup_bonus
            db.session.commit()
        
        flash('Registration successful! Please login.', 'success')
        return redirect(url_for('auth.login'))
    
    return render_template('auth/register.html')

@bp.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        phone_or_username = request.form.get('phone_or_username')
        password = request.form.get('password')
        
        if not phone_or_username or not password:
            flash('Phone/Username and password are required', 'error')
            return render_template('auth/login.html')
        
        # Try to find user by phone or username
        user = User.query.filter(
            (User.phone == phone_or_username) | 
            (User.username == phone_or_username)
        ).first()
        
        if user and user.check_password(password) and user.is_active:
            session['user_id'] = user.id
            user.last_login = datetime.utcnow()
            db.session.commit()
            flash('Login successful!', 'success')
            return redirect(url_for('user.dashboard'))
        else:
            flash('Invalid credentials or account disabled', 'error')
    
    return render_template('auth/login.html')

@bp.route('/logout')
def logout():
    session.pop('user_id', None)
    flash('Logged out successfully', 'success')
    return redirect(url_for('main.index'))
