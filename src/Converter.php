<?php
class Converter {
	/**
	 * @var string
	 */
	private static $chars = 'BCDFGHJKLMNPQRST';

	/**
	 * @var string
	 */
	private $charset = 'UTF-8';

	/**
	 * @param string $charset
	 */
	public function __construct($charset = 'UTF-8') {
		$this->charset = $charset;
	}

	/**
	 * @param string $text
	 * @return string[]
	 */
	public function convert($text) {
		$words = $this->getWords($text);
		$result = array();
		foreach($words as $word) {
			$ngrams = $this->convertWord($word);
			$result = array_merge($result, $ngrams);
		}
		return array_unique($result);
	}

	/**
	 * @param string $word
	 * @return string
	 */
	public function convertWord($word) {
		$word = " {$word} ";
		$word = mb_strtolower($word, $this->charset);
		$ngrams = $this->buildNGrams($word, 3);
		foreach($ngrams as &$ngram) {
			$ngram = $this->encodeWord($ngram);
		}
		return $ngrams;
	}

	/**
	 * @param string $word
	 * @param int $length
	 * @return string[]
	 */
	public function buildNGrams($word, $length) {
		$result = array();
		for($i = 0; $i <= strlen($word) - $length; $i++) {
			$result[] = substr($word, $i, $length);
		}
		return $result;
	}

	/**
	 * @param string $text
	 * @return array
	 */
	public function getWords($text) {
		return preg_split('/[^a-zA-Z]+/u', $text);
	}

	/**
	 * @param string $word
	 * @return string
	 */
	public function encodeWord($word) {
		$codes = '';
		for($i=0; $i<strlen($word); $i++) {
			$byte = $this->getByteFromChar($word[$i]);
			$code = $this->getCode($byte);
			$codes .= $code;
		}
		return $codes;
	}

	/**
	 * @param string $char
	 * @return int
	 */
	public function getByteFromChar($char) {
		return ord($char);
	}

	/**
	 * @param int $byte
	 * @return string
	 */
	public function getCode($byte) {
		$hi = intval($byte / 16);
		$lo = $byte % 16;
		return self::$chars[$hi] . self::$chars[$lo];
	}
}
