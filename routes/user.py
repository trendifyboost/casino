from flask import Blueprint, render_template, request, redirect, url_for, flash, session
from models import User, DepositRequest, WithdrawalRequest, PaymentMethod, Transaction, SiteSettings
from app import db
from utils.helpers import login_required, get_current_user
from datetime import datetime
import os
from werkzeug.utils import secure_filename

bp = Blueprint('user', __name__, url_prefix='/user')

@bp.route('/dashboard')
@login_required
def dashboard():
    user = get_current_user()
    recent_transactions = Transaction.query.filter_by(user_id=user.id)\
        .order_by(Transaction.created_at.desc()).limit(10).all()
    
    # Calculate referral earnings
    referred_users = User.query.filter_by(referred_by=user.id).all()
    
    return render_template('user/dashboard.html', 
                         user=user, 
                         transactions=recent_transactions,
                         referred_users=referred_users)

@bp.route('/deposit', methods=['GET', 'POST'])
@login_required
def deposit():
    if request.method == 'POST':
        amount = float(request.form.get('amount', 0))
        payment_method = request.form.get('payment_method')
        transaction_id = request.form.get('transaction_id')
        
        if amount <= 0:
            flash('Invalid amount', 'error')
            return redirect(url_for('user.deposit'))
        
        # Handle screenshot upload
        screenshot = None
        if 'screenshot' in request.files:
            file = request.files['screenshot']
            if file and file.filename:
                filename = secure_filename(file.filename)
                screenshot = f"deposit_{datetime.utcnow().strftime('%Y%m%d_%H%M%S')}_{filename}"
                file.save(os.path.join('uploads', screenshot))
        
        deposit_request = DepositRequest(
            user_id=get_current_user().id,
            amount=amount,
            payment_method=payment_method,
            transaction_id=transaction_id,
            screenshot=screenshot
        )
        
        db.session.add(deposit_request)
        db.session.commit()
        
        flash('Deposit request submitted successfully', 'success')
        return redirect(url_for('user.dashboard'))
    
    payment_methods = PaymentMethod.query.filter_by(is_active=True).all()
    return render_template('user/deposit.html', payment_methods=payment_methods)

@bp.route('/withdraw', methods=['GET', 'POST'])
@login_required
def withdraw():
    user = get_current_user()
    
    if request.method == 'POST':
        amount = float(request.form.get('amount', 0))
        payment_method = request.form.get('payment_method')
        account_details = request.form.get('account_details')
        
        if amount <= 0:
            flash('Invalid amount', 'error')
            return redirect(url_for('user.withdraw'))
        
        if amount > user.balance:
            flash('Insufficient balance', 'error')
            return redirect(url_for('user.withdraw'))
        
        withdrawal_request = WithdrawalRequest(
            user_id=user.id,
            amount=amount,
            payment_method=payment_method,
            account_details=account_details
        )
        
        db.session.add(withdrawal_request)
        db.session.commit()
        
        flash('Withdrawal request submitted successfully', 'success')
        return redirect(url_for('user.dashboard'))
    
    payment_methods = PaymentMethod.query.filter_by(is_active=True).all()
    return render_template('user/withdraw.html', 
                         payment_methods=payment_methods, 
                         user=user)

@bp.route('/profile', methods=['GET', 'POST'])
@login_required
def profile():
    user = get_current_user()
    
    if request.method == 'POST':
        if 'change_password' in request.form:
            current_password = request.form.get('current_password')
            new_password = request.form.get('new_password')
            confirm_password = request.form.get('confirm_password')
            
            if not user.check_password(current_password):
                flash('Current password is incorrect', 'error')
            elif new_password != confirm_password:
                flash('New passwords do not match', 'error')
            else:
                user.set_password(new_password)
                db.session.commit()
                flash('Password changed successfully', 'success')
        
        elif 'update_profile' in request.form:
            username = request.form.get('username')
            if username and username != user.username:
                if User.query.filter_by(username=username).first():
                    flash('Username already taken', 'error')
                else:
                    user.username = username
                    db.session.commit()
                    flash('Profile updated successfully', 'success')
    
    return render_template('user/profile.html', user=user)
