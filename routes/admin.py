from flask import Blueprint, render_template, request, redirect, url_for, flash, session
from models import (Admin, User, Game, PaymentMethod, DepositRequest, 
                   WithdrawalRequest, HomepageSlider, SiteSettings, Transaction)
from app import db
from utils.helpers import admin_login_required, get_current_admin
from datetime import datetime, timedelta
from werkzeug.utils import secure_filename
import os

bp = Blueprint('admin', __name__, url_prefix='/admin')

@bp.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form.get('username')
        password = request.form.get('password')
        
        admin = Admin.query.filter_by(username=username).first()
        
        if admin and admin.check_password(password) and admin.is_active:
            session['admin_id'] = admin.id
            admin.last_login = datetime.utcnow()
            db.session.commit()
            return redirect(url_for('admin.dashboard'))
        else:
            flash('Invalid credentials', 'error')
    
    return render_template('admin/login.html')

@bp.route('/logout')
def logout():
    session.pop('admin_id', None)
    return redirect(url_for('admin.login'))

@bp.route('/dashboard')
@admin_login_required
def dashboard():
    # Calculate statistics
    total_users = User.query.count()
    total_deposits = db.session.query(db.func.sum(DepositRequest.amount))\
        .filter_by(status='approved').scalar() or 0
    total_withdrawals = db.session.query(db.func.sum(WithdrawalRequest.amount))\
        .filter_by(status='approved').scalar() or 0
    total_games = Game.query.count()
    
    # Today's statistics
    today = datetime.utcnow().date()
    today_deposits = db.session.query(db.func.sum(DepositRequest.amount))\
        .filter(DepositRequest.created_at >= today, DepositRequest.status == 'approved').scalar() or 0
    today_withdrawals = db.session.query(db.func.sum(WithdrawalRequest.amount))\
        .filter(WithdrawalRequest.created_at >= today, WithdrawalRequest.status == 'approved').scalar() or 0
    
    # Pending requests
    pending_deposits = DepositRequest.query.filter_by(status='pending').count()
    pending_withdrawals = WithdrawalRequest.query.filter_by(status='pending').count()
    
    # Top referral users
    top_referrers = User.query.order_by(User.referral_commission.desc()).limit(5).all()
    
    return render_template('admin/dashboard.html',
                         total_users=total_users,
                         total_deposits=total_deposits,
                         total_withdrawals=total_withdrawals,
                         total_games=total_games,
                         today_deposits=today_deposits,
                         today_withdrawals=today_withdrawals,
                         pending_deposits=pending_deposits,
                         pending_withdrawals=pending_withdrawals,
                         top_referrers=top_referrers)

@bp.route('/users')
@admin_login_required
def users():
    page = request.args.get('page', 1, type=int)
    users = User.query.paginate(page=page, per_page=20, error_out=False)
    return render_template('admin/users.html', users=users)

@bp.route('/users/<int:user_id>/edit', methods=['GET', 'POST'])
@admin_login_required
def edit_user(user_id):
    user = User.query.get_or_404(user_id)
    
    if request.method == 'POST':
        action = request.form.get('action')
        
        if action == 'update_balance':
            new_balance = float(request.form.get('balance', 0))
            old_balance = user.balance
            user.balance = new_balance
            
            # Create transaction record
            transaction = Transaction(
                user_id=user.id,
                type='manual_adjustment',
                amount=new_balance - old_balance,
                description=f'Manual balance adjustment by admin'
            )
            db.session.add(transaction)
            
        elif action == 'toggle_status':
            user.is_active = not user.is_active
            
        elif action == 'reset_password':
            new_password = request.form.get('new_password')
            if new_password:
                user.set_password(new_password)
        
        db.session.commit()
        flash('User updated successfully', 'success')
        return redirect(url_for('admin.users'))
    
    return render_template('admin/edit_user.html', user=user)

@bp.route('/games')
@admin_login_required
def games():
    games = Game.query.all()
    return render_template('admin/games.html', games=games)

@bp.route('/games/add', methods=['GET', 'POST'])
@admin_login_required
def add_game():
    if request.method == 'POST':
        title = request.form.get('title')
        category = request.form.get('category')
        winning_percentage = float(request.form.get('winning_percentage', 50))
        min_bet = float(request.form.get('min_bet', 1))
        max_bet = float(request.form.get('max_bet', 1000))
        
        # Handle file uploads
        thumbnail = None
        game_file = None
        
        if 'thumbnail' in request.files:
            file = request.files['thumbnail']
            if file and file.filename:
                filename = secure_filename(file.filename)
                thumbnail = f"game_thumb_{datetime.utcnow().strftime('%Y%m%d_%H%M%S')}_{filename}"
                file.save(os.path.join('uploads', thumbnail))
        
        if 'game_file' in request.files:
            file = request.files['game_file']
            if file and file.filename:
                filename = secure_filename(file.filename)
                game_file = f"game_{datetime.utcnow().strftime('%Y%m%d_%H%M%S')}_{filename}"
                file.save(os.path.join('uploads', game_file))
        
        game = Game(
            title=title,
            category=category,
            thumbnail=thumbnail,
            game_file=game_file,
            winning_percentage=winning_percentage,
            min_bet=min_bet,
            max_bet=max_bet
        )
        
        db.session.add(game)
        db.session.commit()
        
        flash('Game added successfully', 'success')
        return redirect(url_for('admin.games'))
    
    return render_template('admin/add_game.html')

@bp.route('/deposits')
@admin_login_required
def deposits():
    status_filter = request.args.get('status', 'all')
    page = request.args.get('page', 1, type=int)
    
    query = DepositRequest.query
    if status_filter != 'all':
        query = query.filter_by(status=status_filter)
    
    deposits = query.order_by(DepositRequest.created_at.desc())\
        .paginate(page=page, per_page=20, error_out=False)
    
    return render_template('admin/deposits.html', deposits=deposits, status_filter=status_filter)

@bp.route('/deposits/<int:deposit_id>/process', methods=['POST'])
@admin_login_required
def process_deposit(deposit_id):
    deposit = DepositRequest.query.get_or_404(deposit_id)
    action = request.form.get('action')
    admin_notes = request.form.get('admin_notes')
    
    if action == 'approve':
        deposit.status = 'approved'
        deposit.processed_at = datetime.utcnow()
        deposit.admin_notes = admin_notes
        
        # Add amount to user balance
        user = deposit.user
        user.balance += deposit.amount
        
        # Add deposit bonus if configured
        settings = SiteSettings.query.first()
        if settings and settings.deposit_bonus_percentage > 0:
            bonus = deposit.amount * (settings.deposit_bonus_percentage / 100)
            user.bonus_balance += bonus
            deposit.bonus_amount = bonus
        
        # Create transaction record
        transaction = Transaction(
            user_id=user.id,
            type='deposit',
            amount=deposit.amount,
            description=f'Deposit approved - {deposit.payment_method}'
        )
        db.session.add(transaction)
        
        # Handle referral commission
        if user.referred_by and settings:
            commission_rate = settings.referral_commission_percentage / 100
            commission = deposit.amount * commission_rate
            referrer = User.query.get(user.referred_by)
            if referrer:
                referrer.referral_commission += commission
                
                # Create commission transaction
                commission_transaction = Transaction(
                    user_id=referrer.id,
                    type='referral',
                    amount=commission,
                    description=f'Referral commission from {user.full_name}'
                )
                db.session.add(commission_transaction)
        
    elif action == 'reject':
        deposit.status = 'rejected'
        deposit.processed_at = datetime.utcnow()
        deposit.admin_notes = admin_notes
    
    db.session.commit()
    flash(f'Deposit {action}d successfully', 'success')
    return redirect(url_for('admin.deposits'))

@bp.route('/withdrawals')
@admin_login_required
def withdrawals():
    status_filter = request.args.get('status', 'all')
    page = request.args.get('page', 1, type=int)
    
    query = WithdrawalRequest.query
    if status_filter != 'all':
        query = query.filter_by(status=status_filter)
    
    withdrawals = query.order_by(WithdrawalRequest.created_at.desc())\
        .paginate(page=page, per_page=20, error_out=False)
    
    return render_template('admin/withdrawals.html', withdrawals=withdrawals, status_filter=status_filter)

@bp.route('/withdrawals/<int:withdrawal_id>/process', methods=['POST'])
@admin_login_required
def process_withdrawal(withdrawal_id):
    withdrawal = WithdrawalRequest.query.get_or_404(withdrawal_id)
    action = request.form.get('action')
    admin_notes = request.form.get('admin_notes')
    
    if action == 'approve':
        withdrawal.status = 'approved'
        withdrawal.processed_at = datetime.utcnow()
        withdrawal.admin_notes = admin_notes
        
        # Deduct amount from user balance
        user = withdrawal.user
        user.balance -= withdrawal.amount
        
        # Create transaction record
        transaction = Transaction(
            user_id=user.id,
            type='withdrawal',
            amount=-withdrawal.amount,
            description=f'Withdrawal approved - {withdrawal.payment_method}'
        )
        db.session.add(transaction)
        
    elif action == 'reject':
        withdrawal.status = 'rejected'
        withdrawal.processed_at = datetime.utcnow()
        withdrawal.admin_notes = admin_notes
    
    db.session.commit()
    flash(f'Withdrawal {action}d successfully', 'success')
    return redirect(url_for('admin.withdrawals'))

@bp.route('/settings', methods=['GET', 'POST'])
@admin_login_required
def settings():
    settings = SiteSettings.query.first()
    if not settings:
        settings = SiteSettings()
        db.session.add(settings)
        db.session.commit()
    
    if request.method == 'POST':
        settings.site_name = request.form.get('site_name')
        settings.tagline = request.form.get('tagline')
        settings.meta_title = request.form.get('meta_title')
        settings.meta_description = request.form.get('meta_description')
        settings.meta_keywords = request.form.get('meta_keywords')
        settings.currency = request.form.get('currency')
        settings.footer_text = request.form.get('footer_text')
        settings.signup_bonus = float(request.form.get('signup_bonus', 0))
        settings.deposit_bonus_percentage = float(request.form.get('deposit_bonus_percentage', 0))
        settings.referral_commission_percentage = float(request.form.get('referral_commission_percentage', 0))
        settings.maintenance_mode = 'maintenance_mode' in request.form
        
        db.session.commit()
        flash('Settings updated successfully', 'success')
        return redirect(url_for('admin.settings'))
    
    # Get payment methods
    payment_methods = PaymentMethod.query.all()
    
    return render_template('admin/settings.html', settings=settings, payment_methods=payment_methods)

@bp.route('/sliders')
@admin_login_required
def sliders():
    sliders = HomepageSlider.query.order_by(HomepageSlider.order_position).all()
    return render_template('admin/sliders.html', sliders=sliders)

@bp.route('/sliders/add', methods=['GET', 'POST'])
@admin_login_required
def add_slider():
    if request.method == 'POST':
        title = request.form.get('title')
        link_url = request.form.get('link_url')
        order_position = int(request.form.get('order_position', 0))
        
        # Handle image upload
        image_path = None
        if 'image' in request.files:
            file = request.files['image']
            if file and file.filename:
                filename = secure_filename(file.filename)
                image_path = f"slider_{datetime.utcnow().strftime('%Y%m%d_%H%M%S')}_{filename}"
                file.save(os.path.join('uploads', image_path))
        
        if image_path:
            slider = HomepageSlider(
                title=title,
                image_path=image_path,
                link_url=link_url,
                order_position=order_position
            )
            
            db.session.add(slider)
            db.session.commit()
            
            flash('Slider added successfully', 'success')
            return redirect(url_for('admin.sliders'))
        else:
            flash('Please select an image', 'error')
    
    return render_template('admin/add_slider.html')
