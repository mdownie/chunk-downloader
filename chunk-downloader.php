<?php

/*
 * @class File
 * Represents a file to be downloaded
 */

class File {
	protected $chunks = [];
	private $url;
	private $filesize;
	private $curl;
	
	/*
	 * Constructor
	 * @param string $url URL of file to be downloaded.
	 * @return void
	 */
	
	function __construct($url) {
		$this->url = $url;
		$this->curl = curl_init($this->url);
		curl_setopt_array($this->curl, [
			CURLOPT_HEADER => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_BINARYTRANSFER => true,
			CURLOPT_NOBODY => true
		]);
		$result = curl_exec($this->curl);
		preg_match('/Content-Length: (\d*)/', $result, $matches);
		$this->filesize = $matches[1];
		curl_setopt($this->curl, CURLOPT_NOBODY, false);
		curl_setopt($this->curl, CURLOPT_HEADER, false);
	}
	
	/*
	 * Function to download file
	 * @param int $chunk_size Size in bytes of each chunk to be downloaded, defaults to 1mb(1024b)
	 * @param int $num_chunks Number of chunks to be downloaded, defaults to 4
	 * @return void
	 */
	
	public function download($chunk_size = 1024, $num_chunks = 4) {
		$x = 0;
		if ($this->filesize < 4096) {
			$chunk_size = ceil($this->filesize / 4);
		}
		while ($x < $num_chunks) {
			$this->chunks[] = new FileChunk(($x * $chunk_size) . "-" . ((($x + 1) * $chunk_size) - 1));
			$this->chunks[count($this->chunks) - 1]->downloadChunk($this->curl);
			$x++;
		}
		curl_close($this->curl);
	}
	
	/*
	 * Function to write file downloaded
	 * @param $filename Name/location of file to be written
	 * @return void
	 */
	
	public function writeFile($filename) {
		$handle = fopen($filename, 'w');
		foreach ($this->chunks as $chunk) {
			fwrite($handle, $chunk->data);
		}
		fclose($handle);
	}
}

/*
 * @class FileChunk
 * Wrapper for each file chunk
 * Would allow for paralell processing using the range of each chunk
 */

class FileChunk {
	public $range;
	public $data;
	
	/*
	 * Constructor
	 * @param string $range A range value in the format of 'from-to' where from and to are integers
	 */
	
	function __construct($range) {
		$this->range = $range;
	}
	
	/*
	 * Function to download a chunk of a file
	 * @param curl resource $curl The curl object to make the request on
	 * @return void
	 */
	
	public function downloadChunk($curl) {
		curl_setopt($curl, CURLOPT_RANGE, $this->range);
		$this->data = curl_exec($curl);
	}
}

if (empty($argv[1])) {
	die("Fata error. No URL/File set." . PHP_EOL . "Usage: /usr/bin/php chunk-downloaded.php url_of_file filename_to_write");
}
$url = $argv[1];
$write_filename = $argv[2] ?? 'examplefile.jar';
$var = new File($url);
$var->download();
$var->writeFile($write_filename);