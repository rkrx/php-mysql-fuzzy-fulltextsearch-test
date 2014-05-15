<?php
class Database {
	/**
	 * @var PDO
	 */
	private $db = null;

	/**
	 * @var PDOStatement[]
	 */
	private $stmt = array();

	/**
	 * @var Converter
	 */
	private $converter;

	/**
	 * @param string $dsn
	 * @param string $user
	 * @param string $pass
	 * @param Converter $converter
	 */
	public function __construct($dsn, $user, $pass, Converter $converter) {
		$this->converter = $converter;
		$this->db = new PDO($dsn, $user, $pass, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->db->exec('
			CREATE TABLE IF NOT EXISTS fulltext_test (
				id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				text_plain LONGTEXT NOT NULL,
				text_search LONGTEXT NOT NULL,
				PRIMARY KEY (id),
				FULLTEXT INDEX search (text_search)
			) ENGINE=MyISAM;
		');
		$this->stmt['insert'] = $this->db->prepare('INSERT INTO fulltext_test SET text_plain=:plain, text_search=:search;');
		$this->stmt['search'] = $this->db->prepare('
			SELECT
				id,
				text_plain
			FROM
				fulltext_test
			WHERE
				MATCH (text_search) AGAINST (:search IN BOOLEAN MODE) > :minOccurrences
			ORDER BY
				MATCH (text_search) AGAINST (:search IN BOOLEAN MODE) * MATCH (text_search) AGAINST (:search) DESC
			LIMIT
				:limit;');
	}

	/**
	 * @return PDO
	 */
	public function getPdo() {
		return $this->db;
	}

	/**
	 * @return $this
	 */
	public function truncate() {
		$this->db->exec('TRUNCATE fulltext_test;');
		return $this;
	}

	/**
	 * @param string $line
	 * @return $this
	 */
	public function insert($line) {
		$ngrams = $this->converter->convert($line);
		$search = join(' ', $ngrams);
		$stmt = $this->stmt['insert'];
		$stmt->bindValue('plain', $line);
		$stmt->bindValue('search', $search);
		$stmt->execute();
		return $this;
	}

	/**
	 * @param string $query
	 * @param int $limit
	 * @return string
	 */
	public function search($query, $limit = 9999) {
		$ngrams = $this->converter->convert($query);

		$ngramCount = count($ngrams);
		$ngramQuery = join(' ', $ngrams);

		$minOccurrences = min(ceil($ngramCount * 0.3), $ngramCount - 3);

		$stmt = $this->stmt['search'];
		$stmt->bindValue('search', $ngramQuery);
		$stmt->bindValue('minOccurrences', $minOccurrences);
		$stmt->bindValue('limit', $limit, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}