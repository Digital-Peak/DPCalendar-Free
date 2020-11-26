<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
namespace DPCalendar\Helper;

/**
 * Helper class to make some HTTP requests. Contains helper functions for the most popule
 * methods GET, POST, PUT and DELETE.
 *
 * Needs curl to work properly.
 */
class HTTP
{
	/**
	 * Helper function to make a GET request.
	 *
	 * @see HTTP::request()
	 * @param string   $url
	 * @param string   $userOrToken
	 * @param string   $password
	 * @param string[] $headers
	 * @param array    $curlOptions
	 *
	 * @return \stdClass
	 * @throws \Exception
	 */
	public function get($url, $userOrToken = null, $password = null, $headers = [], $curlOptions = [])
	{
		return $this->request($url, null, $userOrToken, $password, $headers, $curlOptions, 'get');
	}

	/**
	 * Helper function to make a POST request.
	 *
	 * @param string   $url
	 * @param string   $body
	 * @param string   $userOrToken
	 * @param string   $password
	 * @param string[] $headers
	 * @param array    $curlOptions
	 *
	 * @see HTTP::request()
	 * @return \stdClass
	 * @throws \Exception
	 */
	public function post($url, $body, $userOrToken = null, $password = null, $headers = [], $curlOptions = [])
	{
		return $this->request($url, $body, $userOrToken, $password, $headers, $curlOptions, 'post');
	}

	/**
	 * Helper function to make a PUT request.
	 *
	 * @param string   $url
	 * @param string   $body
	 * @param string   $userOrToken
	 * @param string   $password
	 * @param string[] $headers
	 * @param array    $curlOptions
	 *
	 * @see HTTP::request()
	 * @return \stdClass
	 * @throws \Exception
	 */
	public function put($url, $body, $userOrToken = null, $password = null, $headers = [], $curlOptions = [])
	{
		return $this->request($url, $body, $userOrToken, $password, $headers, $curlOptions, 'put');
	}

	/**
	 * Helper function to make a DELETE request.
	 *
	 * @param string   $url
	 * @param string   $userOrToken
	 * @param string   $password
	 * @param string[] $headers
	 * @param array    $curlOptions
	 *
	 * @see HTTP::request()
	 * @return \stdClass
	 * @throws \Exception
	 */
	public function delete($url, $userOrToken = null, $password = null, $headers = [], $curlOptions = [])
	{
		return $this->request($url, null, $userOrToken, $password, $headers, $curlOptions, 'delete');
	}

	/**
	 * Helper function to get some data from an url. The result is the object from the response. It automatically detects
	 * if the response is a JSON strings and creates a proper object out of it. The resulting object contains a dp property
	 * which contains the following fields:
	 * - body The response body.
	 * - info The transaction information like http response code as object.
	 * - headers The headers of the response.
	 *
	 * If user is set but no password, then it is assumed it is a bearer token.
	 *
	 * @param string   $url
	 * @param string   $body
	 * @param string   $userOrToken
	 * @param string   $password
	 * @param string[] $headers
	 * @param string   $method
	 *
	 * @return \stdClass
	 * @throws \Exception
	 */
	public function request($url, $body = '', $userOrToken = null, $password = null, $headers = [], $curlOptions = [], $method = 'get')
	{
		if (!function_exists('curl_version')) {
			throw new \Exception('Curl must be installed, please contact an administrator!');
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
		curl_setopt($ch, CURLOPT_USERAGENT, 'DPCalendar');

		$headers[] = 'Accept: application/json';
		if ($body && strpos($body, '{') === 0) {
			$headers[] = 'Content-Type: application/json';
		}
		if ($body && strpos($body, '<') === 0) {
			$headers[] = 'Content-Type: text/xml';
		}
		if ($userOrToken && !$password) {
			$headers[] = 'Authorization: Bearer ' . $userOrToken;
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		if ($userOrToken && $password) {
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, $userOrToken . ':' . $password);
		}

		if ($body) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}

		$headers = [];
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$headers) {
			$headerData = explode(':', $header, 2);

			// Ignore invalid headers
			if (count($headerData) < 2) {
				return strlen($header);
			}

			$headers[strtolower(trim($headerData[0]))][] = trim(trim($headerData[1]), '\"');

			return strlen($header);
		});

		foreach ($curlOptions as $option => $value) {
			curl_setopt($ch, $option, $value);
		}

		$output = curl_exec($ch);
		$info   = curl_getinfo($ch);
		$error  = curl_errno($ch) ? curl_error($ch) : null;

		curl_close($ch);

		if ($error) {
			throw new \Exception($error, $info['http_code']);
		}

		$data = new \stdClass();
		if (strpos($output, '{') === 0 || strpos($output, '[') === 0) {
			$data = json_decode($output);
			if ($data === null) {
				throw new \Exception('Invalid json data returned!!');
			}
		}

		if (is_array($data)) {
			$data = (object)['data' => $data];
		}

		$data->dp          = new \stdClass();
		$data->dp->body    = $output;
		$data->dp->info    = (object)$info;
		$data->dp->headers = $headers;

		return $data;
	}
}
