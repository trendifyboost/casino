# MySQL Configuration for Shared Hosting
# Copy this file to your shared hosting and update the DATABASE_URL

import os

# MySQL Database Configuration
# Update these values with your shared hosting MySQL details
MYSQL_CONFIG = {
    'host': 'localhost',  # Usually localhost on shared hosting
    'user': 'Nasimul',
    'password': 'Nasimul24#',
    'database': 'na',
    'port': 3306,  # Default MySQL port
    'charset': 'utf8mb4'
}

# Construct MySQL URL
def get_mysql_url():
    return f"mysql+pymysql://{MYSQL_CONFIG['user']}:{MYSQL_CONFIG['password']}@{MYSQL_CONFIG['host']}:{MYSQL_CONFIG['port']}/{MYSQL_CONFIG['database']}?charset={MYSQL_CONFIG['charset']}"

# For shared hosting deployment, set this as your DATABASE_URL environment variable
# os.environ['DATABASE_URL'] = get_mysql_url()

# Example usage in your shared hosting environment:
# 1. Update the MYSQL_CONFIG values above
# 2. Add this to your app initialization:
#    from mysql_config import get_mysql_url
#    os.environ['DATABASE_URL'] = get_mysql_url()