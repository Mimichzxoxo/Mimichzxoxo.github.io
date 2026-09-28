import pymysql
import os

MYSQL_CONFIG = {
    'host': os.getenv('MYSQL_HOST', 'localhost'),
    'user': os.getenv('MYSQL_USER', 'root'),
    'password': os.getenv('MYSQL_PASSWORD', ''),  # Default XAMPP password is empty
    'database': os.getenv('MYSQL_DB', 'db_wargaconnect'),
    'port': int(os.getenv('MYSQL_PORT', 3306)),
    'cursorclass': pymysql.cursors.DictCursor,
    'autocommit': True
}

class MySQLCursorWrapper:
    def __init__(self, cursor):
        self.cursor = cursor

    def fetchone(self):
        return self.cursor.fetchone()

    def fetchall(self):
        return self.cursor.fetchall()

class MySQLConnectionWrapper:
    def __init__(self):
        self.conn = pymysql.connect(**MYSQL_CONFIG)

    def execute(self, sql, args=None):
        cursor = self.conn.cursor()
        # Convert ? placeholders to %s for PyMySQL compatibility
        sql_converted = sql.replace('?', '%s')
        if args is None:
            cursor.execute(sql_converted)
        else:
            cursor.execute(sql_converted, args)
        return MySQLCursorWrapper(cursor)

    def executemany(self, sql, args_list):
        cursor = self.conn.cursor()
        sql_converted = sql.replace('?', '%s')
        cursor.executemany(sql_converted, args_list)
        return MySQLCursorWrapper(cursor)

    def commit(self):
        self.conn.commit()

    def close(self):
        self.conn.close()

def get_mysql_db():
    return MySQLConnectionWrapper()
