from flask import Blueprint, render_template, request, redirect, url_for, flash
from models import HomepageSlider, SiteSettings, Game

bp = Blueprint('main', __name__)

@bp.route('/')
def index():
    # Get active sliders
    sliders = HomepageSlider.query.filter_by(is_active=True)\
        .order_by(HomepageSlider.order_position).all()
    
    # Get featured games
    featured_games = Game.query.filter_by(is_active=True).limit(8).all()
    
    # Get site settings
    settings = SiteSettings.query.first()
    
    return render_template('index.html', 
                         sliders=sliders, 
                         games=featured_games,
                         settings=settings)

@bp.route('/games')
def games():
    category = request.args.get('category', 'all')
    
    if category == 'all':
        games = Game.query.filter_by(is_active=True).all()
    else:
        games = Game.query.filter_by(is_active=True, category=category).all()
    
    return render_template('games.html', games=games, category=category)

@bp.route('/games/<int:game_id>')
def play_game(game_id):
    game = Game.query.get_or_404(game_id)
    if not game.is_active:
        flash('Game is not available', 'error')
        return redirect(url_for('main.games'))
    
    return render_template('play_game.html', game=game)
