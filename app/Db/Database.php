<?php

namespace App\Db;

use \PDO;
use \PDOException;

/**
 * Database Abstraction Layer
 *
 * Handles all database operations using PDO for MySQL connections.
 * Supports CRUD operations: Create, Read, Update, Delete.
 *
 * @package App\Db
 * @version 2.0
 */
class Database
{
  /**
   * Database host address
   * Use 'mysql' when running in Docker, 'localhost' for local development
   *
   * @var string
   */
  const HOST = 'mysql';

  /**
   * Database name
   *
   * @var string
   */
  const NAME = 'myapp_db';

  /**
   * Database user/username
   *
   * @var string
   */
  const USER = 'appuser';

  /**
   * Database password
   *
   * @var string
   */
  const PASS = 'app_password';

  /**
   * Database port
   * Default MySQL port is 3306
   *
   * @var int
   */
  const PORT = 3306;

  /**
   * Current table name for query operations
   *
   * @var string
   */
  private $table;

  /**
   * PDO connection instance
   *
   * @var PDO|null
   */
  private $connection;

  /**
   * Constructor
   *
   * Initializes the database connection and sets the table name.
   * Throws exception if connection fails.
   *
   * @param string|null $table Table name for CRUD operations
   * @throws PDOException If connection fails
   */
  public function __construct($table = null)
  {
    $this->table = $table;
    $this->setConnection();
  }

  /**
   * Establish PDO connection to MySQL database
   *
   * Creates a new PDO instance with proper error handling.
   * Sets error mode to throw exceptions for better debugging.
   *
   * @return void
   * @throws PDOException If connection fails
   */
  private function setConnection()
  {
    try {
      $dsn = 'mysql:host=' . self::HOST .
        ';port=' . self::PORT .
        ';dbname=' . self::NAME .
        ';charset=utf8mb4';

      $this->connection = new PDO(
        $dsn,
        self::USER,
        self::PASS,
        [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES => false,
        ]
      );
    } catch (PDOException $e) {
      die('DATABASE ERROR: ' . $e->getMessage());
    }
  }

  /**
   * Get the current PDO connection instance
   *
   * @return PDO The active database connection
   */
  public function getConnection()
  {
    return $this->connection;
  }

  /**
   * Execute raw SQL queries with parameter binding
   *
   * Uses prepared statements to prevent SQL injection.
   * Supports both named (:param) and positional (?) placeholders.
   *
   * Example:
   *   $db->execute("SELECT * FROM users WHERE id = ?", );
   *   $db->execute("SELECT * FROM users WHERE email = :email", ['email' => 'user@example.com']);
   *
   * @param string $query SQL query with placeholders
   * @param array $params Parameters to bind to query
   * @return \PDOStatement The executed statement object
   * @throws PDOException If query execution fails
   */
  public function execute($query, $params = [])
  {
    try {
      $statement = $this->connection->prepare($query);
      $statement->execute($params);
      return $statement;
    } catch (PDOException $e) {
      die('QUERY ERROR: ' . $e->getMessage());
    }
  }

  /**
   * Insert a new record into the table
   *
   * Automatically constructs INSERT query from associative array.
   * Uses prepared statements to prevent SQL injection.
   *
   * Example:
   *   $db = new Database('users');
   *   $id = $db->insert([
   *       'name' => 'John Doe',
   *       'email' => 'john@example.com',
   *       'password' => 'hashed_password'
   *   ]);
   *   echo "Inserted with ID: " . $id;
   *
   * @param array $values Associative array of [column => value]
   * @return string|int The last inserted row ID
   * @throws PDOException If insert fails
   */
  public function insert($values)
  {
    // Extract field names and create parameter placeholders
    $fields = array_keys($values);
    $binds = array_pad([], count($fields), '?');

    // Build INSERT query
    $query = 'INSERT INTO ' . $this->table .
      ' (' . implode(',', $fields) . ') ' .
      'VALUES (' . implode(',', $binds) . ')';

    // Execute insert with values
    $this->execute($query, array_values($values));

    // Return the auto-incremented ID
    return $this->connection->lastInsertId();
  }

  /**
   * Select and fetch records from the table
   *
   * Builds SELECT query with optional WHERE, ORDER BY, LIMIT clauses.
   * Returns PDOStatement for flexible result handling.
   *
   * Example:
   *   $db = new Database('users');
   *
   *   // Select all users
   *   $result = $db->select();
   *
   *   // Select with WHERE clause
   *   $result = $db->select('status = "active"');
   *
   *   // Select with ORDER and LIMIT
   *   $result = $db->select(null, 'created_at DESC', '0,10');
   *
   *   // Select specific fields
   *   $result = $db->select(null, null, null, 'id, name, email');
   *
   *   // Fetch results
   *   while ($row = $result->fetch()) {
   *       echo $row['name'];
   *   }
   *
   * @param string|null $where WHERE clause without "WHERE" keyword
   * @param string|null $order ORDER BY clause without "ORDER BY" keyword
   * @param string|null $limit LIMIT clause. Format: "10" or "0,10"
   * @param string $fields Comma-separated field names. Default: '*'
   * @return \PDOStatement Result set containing fetched records
   * @throws PDOException If select fails
   */
  public function select($where = null, $order = null, $limit = null, $fields = '*')
  {
    // Build conditional clauses (PHP 8.1+ safe: check null before strlen)
    $where = !is_null($where) && strlen($where) ? 'WHERE ' . $where : '';
    $order = !is_null($order) && strlen($order) ? 'ORDER BY ' . $order : '';
    $limit = !is_null($limit) && strlen($limit) ? 'LIMIT ' . $limit : '';

    // Build SELECT query
    $query = 'SELECT ' . $fields . ' FROM ' . $this->table . ' ' .
      $where . ' ' . $order . ' ' . $limit;

    // Execute and return statement
    return $this->execute($query);
  }

  /**
   * Update existing records in the table
   *
   * Updates multiple records matching WHERE condition.
   * Uses prepared statements to prevent SQL injection.
   *
   * Example:
   *   $db = new Database('users');
   *   $success = $db->update(
   *       'id = 5',
   *       [
   *           'name' => 'Jane Doe',
   *           'email' => 'jane@example.com'
   *       ]
   *   );
   *
   * @param string $where WHERE clause to identify records (without "WHERE" keyword)
   * @param array $values Associative array of [column => value] to update
   * @return bool Always returns true if successful
   * @throws PDOException If update fails
   */
  public function update($where, $values)
  {
    // Extract field names
    $fields = array_keys($values);

    // Build UPDATE query with dynamic field assignments
    $setClause = implode('=?,', $fields) . '=?';
    $query = 'UPDATE ' . $this->table . ' SET ' . $setClause . ' WHERE ' . $where;

    // Execute update with values
    $this->execute($query, array_values($values));

    return true;
  }

  /**
   * Delete records from the table
   *
   * Removes records matching the WHERE condition.
   * Be careful with WHERE clause - omitting it deletes all records!
   *
   * Example:
   *   $db = new Database('users');
   *   $db->delete('id = 5');           // Delete specific user
   *   $db->delete('status = "inactive"'); // Delete multiple records
   *
   * @param string $where WHERE clause to identify records (without "WHERE" keyword)
   * @return bool Always returns true if successful
   * @throws PDOException If delete fails
   */
  public function delete($where)
  {
    // Build DELETE query
    $query = 'DELETE FROM ' . $this->table . ' WHERE ' . $where;

    // Execute delete
    $this->execute($query);

    return true;
  }

  /**
   * Set table name for subsequent operations
   *
   * Allows changing table without creating new instance.
   *
   * Example:
   *   $db = new Database('users');
   *   $db->setTable('posts');
   *   $db->select();  // Selects from posts table
   *
   * @param string $table Table name
   * @return void
   */
  public function setTable($table)
  {
    $this->table = $table;
  }

  /**
   * Get current table name
   *
   * @return string Current table name
   */
  public function getTable()
  {
    return $this->table;
  }

  /**
   * Count records in table with optional WHERE clause
   *
   * Example:
   *   $db = new Database('users');
   *   $total = $db->count();                    // Total records
   *   $active = $db->count('status = "active"'); // Active users
   *
   * @param string|null $where Optional WHERE clause
   * @return int Number of matching records
   */
  public function count($where = null)
  {
    $where = !is_null($where) && strlen($where) ? 'WHERE ' . $where : '';
    $query = 'SELECT COUNT(*) as total FROM ' . $this->table . ' ' . $where;

    $result = $this->execute($query);
    $row = $result->fetch();

    return (int) $row['total'];
  }
}
