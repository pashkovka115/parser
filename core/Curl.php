<?php

class Curl {

	private $curl;
	private $base_url;
	private $options = [];
	private $headers;


	public function __construct( $url ) {
		$this->curl     = curl_init( $url );
		$this->base_url = $url;
		$this->setOption( CURLOPT_RETURNTRANSFER, TRUE );
	}

	public function __destruct() {
		curl_close( $this->curl );
	}

	public function setOption( int $option, $value ) {
		$this->options[ $option ] = $value;
		curl_setopt( $this->curl, $option, $value );
		return $this;
	}

	public function headersOn() {
		$this->setOption(CURLOPT_HEADER, TRUE);

		return $this;
	}

	public function headersOff() {
		$this->setOption(CURLOPT_HEADER, FALSE);
		return $this;
	}

    /**
     * @param $url - адрес вглубь сайта или пустая строка если на главную
     * @return bool|mixed|string
     */
	public function request( $url ) {
		if ( $url[0] !== '/' ) {
			$url = '/' . $url;
		}
		$this->setOption(CURLOPT_URL, rtrim( $this->base_url, '/' ) . $url);
		$data = curl_exec( $this->curl );

		if (isset($this->options[CURLOPT_HEADER]) and $this->options[CURLOPT_HEADER] == TRUE) {

			$curl_info = curl_getinfo($this->curl);
			$header_size = $curl_info['header_size'];
			// TODO: при редиректе все заголовки запишутся или только последние?
			$this->headers = substr($data, 0, $header_size);

			return substr($data, $header_size);
		}

		return $data;
	}

	public function sslOn( ) {
		$this->setOption(CURLOPT_SSL_VERIFYPEER, TRUE);
		$this->setOption(CURLOPT_SSL_VERIFYHOST, TRUE);

		return $this;
	}

	public function sslOff( ) {
		$this->setOption(CURLOPT_SSL_VERIFYPEER, FALSE);
		$this->setOption(CURLOPT_SSL_VERIFYHOST, FALSE);

		return $this;
	}

	public function cookieSave($file){
		$this->setOption(CURLOPT_COOKIEJAR, $_SERVER['DOCUMENT_ROOT'] . '/' . $file);
		$this->setOption(CURLOPT_COOKIEFILE, $_SERVER['DOCUMENT_ROOT'] . '/' . $file);
		return $this;
	}

	public function followToRedirectOn() {
		$this->setOption(CURLOPT_FOLLOWLOCATION, TRUE);
		return $this;
	}

	public function followToRedirectOff() {
		$this->setOption(CURLOPT_FOLLOWLOCATION, FALSE);
		return $this;
	}

	/**
	 * Устанавливает содержимое заголовка "Referer: ", который будет использован в HTTP-запросе.
	 * На пример: "http://www.google.com",
	 * "http://www.yandex.ru/", "http://www.ya.ru/",
	 * "", "https://mail.ru/", "https://www.yahoo.com/"
	 * @url string $url
	 */
	public function refererSet($url = 'http://www.google.com') {
		$this->setOption(CURLOPT_REFERER, $url);
		return $this;
	}

	/**
	 * Настройка конфигурации для метода POST
	 *
	 * @param mixed $post
	 * array - ассоциативный массив с параметрами
	 * false - отлючить обращение методом POST
	 */
	public function post($data){
		if ($data === false) {
			$this->setOption(CURLOPT_POST, false);
			return $this;
		}

		$this->setOption(CURLOPT_POST, true);
		$this->setOption(CURLOPT_POSTFIELDS, http_build_query($data));
		return $this;
	}

	/**
	 * Добавить 1 произвольный http-заголовок к запросу
	 *
	 * @param string $header
	 * TODO: проверить методы addHeader() и addHeaders()
	 */
	public function addHeader($header){
		$this->options[CURLOPT_HTTPHEADER][] = $header;
		$this->setOption(CURLOPT_HTTPHEADER, $this->options[CURLOPT_HTTPHEADER]);
		return $this;
	}

	/**
	 * Добавить несколько произвольных http-заголовоков к запросу
	 *
	 * @param array $headers
	 */
	public function addHeaders(array $headers){
		foreach($headers as $h)
			$this->options[CURLOPT_HTTPHEADER][] = $h;

		$this->setOption(CURLOPT_HTTPHEADER, $this->options[CURLOPT_HTTPHEADER]);
		return $this;
	}

	/**
	 * Очиситить массив произвольных http-заголовков
	 */
	public function clearHeaders(){
		$this->options[CURLOPT_HTTPHEADER] = array();
		$this->setOption(CURLOPT_HTTPHEADER, $this->options[CURLOPT_HTTPHEADER]);
		return $this;
	}

	/**
	 * Загрузить конфигурацию из файла
	 *
	 * @param string $file
	 */
	public function configLoad($file){
		$data = file_get_contents($file);
		$data = unserialize($data);

		curl_setopt_array($this->curl, $data);

		foreach($data as $key => $val){
			$this->options[$key] = $val;
		}

		return $this;
	}

	/**
	 * Сохранить конфигурацию в файл
	 *
	 * @param string $file
	 */
	public function configSave($file){
		$data = serialize($this->options);
		file_put_contents($file, $data);
		return $this;
	}

	/**
	 * Устанавливает браузер
	 *
	 * @agent string $agent
	 */
	public function userAgentSet($agent = 'Googlebot') {
		$this->setOption(CURLOPT_USERAGENT, $agent);
		return $this;
	}

	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * случайное получение юзерагента
	 *
	 * @return $this
	 */
	public function userAgentRandom(){
		$user_agents = array(
			'Googlebot',
			'MSNBot',
			'Mozilla/1.1 (compatible; MSPIE 2.0; Windows CE)',
			'Mozilla/1.10 [en] (Compatible; RISC OS 3.70; Oregano 1.10)',
			'Mozilla/1.22 (compatible; MSIE 1.5; Windows NT)',
			'Mozilla/1.22 (compatible; MSIE 2.0; Windows 95)',
			'Mozilla/1.22 (compatible; MSIE 2.0d; Windows NT)',
			'Mozilla/1.22 (compatible; MSIE 5.01; PalmOS 3.0) EudoraWeb 2',
			'Mozilla/2.0 (compatible; MSIE 3.01; Windows 98)',
			'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)',
			'Mozilla/4.0 (compatible; MSIE 5.0; Mac_PowerPC) Opera 6.0 [en]',
			'Mozilla/4.0 (compatible; MSIE 5.0; SunOS 5.9 sun4u; X11)',
			'Mozilla/4.0 (compatible; MSIE 5.0; Windows 2000) Opera 6.03 [ru]',
			'Mozilla/4.0 (compatible; MSIE 5.17; Mac_PowerPC)',
			'Mozilla/4.0 (compatible; MSIE 5.23; Mac_PowerPC)',
			'Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.0)',
			'Mozilla/4.0 (compatible; MSIE 6.0; ; Linux armv5tejl; U) Opera 8.02 [en_US] Maemo browser 0.4.31 N770/SU-18',
			'Mozilla/4.0 (compatible; MSIE 6.0; MSN 2.5; Windows 98)',
			'Mozilla/4.0 (compatible; MSIE 6.0; Nitro) Opera 8.50 [de]',
			'Mozilla/4.0 (compatible; MSIE 6.0; Nitro) Opera 8.50 [en]',
			'Mozilla/4.0 (compatible; MSIE 6.0; Nitro) Opera 8.50 [es]',
			'Mozilla/4.0 (compatible; MSIE 6.0; Nitro) Opera 8.50 [fr]',
			'Mozilla/4.0 (compatible; MSIE 6.0; Nitro) Opera 8.50 [it]',
			'Mozilla/4.0 (compatible; MSIE 6.0; Nitro) Opera 8.50 [ja]',
			'Mozilla/4.0 (compatible; MSIE 6.0; Symbian OS; Nokia 6600/5.27.0; 1657) Opera 8.60 [ru]',
			'Mozilla/4.0 (compatible; MSIE 6.0; Symbian OS; Nokia 6600/5.27.0; 1665) Opera 8.60 [en]',
			'Mozilla/4.0 (compatible; MSIE 6.0; Symbian OS; Nokia 6600/5.27.0; 1665) Opera 8.60 [fr]',
			'Mozilla/4.0 (compatible; MSIE 6.0; Symbian OS; Nokia 6600/5.27.0; 6329) Opera 8.00 [it]',
			'Mozilla/4.0 (compatible; MSIE 6.0; Symbian OS; Nokia 6600/5.27.0; 6936) Opera 8.50 [zw]',
			'Mozilla/4.0 (compatible; MSIE 6.0; Symbian OS; Nokia 6600/5.27.0; 9399) Opera 8.65 [ja]',
			'Mozilla/4.0 (compatible; MSIE 6.0; Symbian OS; Nokia 6600/5.27.0; 9424) Opera 8.65 [ch]',
			'Mozilla/4.0 (compatible; MSIE 6.0; Symbian OS; Nokia 6630/4.03.38; 6937) Opera 8.50 [es]',
			'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)',
			'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0; .NET CLR 2.0.50727)',
			'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)',
			'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727)',
			'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; en) Opera 8.50',
			'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.2; SV1; .NET CLR 1.1.4322)',
			'Mozilla/4.0 (compatible; MSIE 6.0; X11; Linux x86_64; ru) Opera 10.10',
			'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; SV1; Arcor 5.005; .NET CLR 1.0.3705; .NET CLR 1.1.4322)',
			'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; YPC 3.0.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)',
			'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)',
			'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; WOW64; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; .NET CLR 3.5.21022)',
			'Mozilla/4.0 (compatible; MSIE 7.0b; Win32)',
			'Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.1)',
			'Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 6.0)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; WOW64; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; .NET CLR 3.5.21022)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; WOW64; Trident/4.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.5.21022; .NET CLR 3.5.30729; .NET CLR 3.0.30618)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0)',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Win64; x64; Trident/4.0; .NET CLR 2.0.50727; SLCC2; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; Tablet PC 2.0)',
			'Mozilla/4.1 (compatible; MSIE 5.0; Symbian OS; Nokia 6600;452) Opera 6.20 [ru]',
			'Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US; rv:1.8.0.7) Gecko/20060909 Firefox/1.5.0.7',
			'Mozilla/5.0 (Macintosh; U; PPC Mac OS X Mach-O; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7',
			'Mozilla/5.0 (Windows NT 5.1; U; en) Opera 8.50',
			'Mozilla/5.0 (Windows NT 5.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1',
			'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/534.36 (KHTML, like Gecko) Chrome/12.0.742.53 Safari/534.36 QQBrowser/6.3.8908.201',
			'Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.3a) Gecko/20030105 Phoenix/0.5',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.8.0.1) Gecko/20060111 Firefox/1.5.0.1',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US)',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.2.149.27 Safari/525.13',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.19 (KHTML, like Gecko) Chrome/0.4.154.25 Safari/525.19',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/532.0 (KHTML, like Gecko) Chrome/3.0.195.10 Safari/532.0',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.6b) Gecko/20031215 Firebird/0.7+',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.10) Gecko/20050716 Firefox/1.0.6',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.0.4) Gecko/20060516 SeaMonkey/1.0.2',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.0.6) Gecko/20060728 SeaMonkey/1.0.4',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.1) Gecko/20090624 Firefox/3.5',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.22) Gecko/20110902 Firefox/3.6.22',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; nl-NL; rv:1.7.5) Gecko/20041202 Firefox/1.0',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; nl; rv:1.8) Gecko/20051107 Firefox/1.5',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.8.1.17) Gecko/20080829 Firefox/2.0.0.17',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.8.1.19) Gecko/20081201 Firefox/2.0.0.19',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.8.1.20) Gecko/20081217 Firefox/2.0.0.20',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9) Gecko/2008052906 Firefox/3.0',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.2) Gecko/2008091620 Firefox/3.0.2',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3 (.NET CLR 3.5.30729)',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.7) Gecko/2009021910 Firefox/3.0.7',
			'Mozilla/5.0 (Windows; U; Windows NT 5.2; ru; rv:1.9.0.5) Gecko/2008120122 Firefox/3.0.5',
			'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.8.0.4) Gecko/20060508 Firefox/1.5.0.4',
			'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10',
			'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.1.1) Gecko/20090715 Firefox/3.5.1',
			'Mozilla/5.0 (Windows; U; Windows NT 6.0; ru; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3',
			'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/525.19 (KHTML, like Gecko) Chrome/1.0.154.65 Safari/525.19',
			'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/532.5 (KHTML, like Gecko) Chrome/4.0.249.89 Safari/532.5',
			'Mozilla/5.0 (X11; U; FreeBSD i386; en-US; rv:1.7.8) Gecko/20050609',
			'Mozilla/5.0 (X11; U; FreeBSD i386; en-US; rv:1.7.8) Gecko/20050609 Firefox/1.0.4',
			'Mozilla/5.0 (X11; U; Linux i686 (x86_64); en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9',
			'Mozilla/5.0 (X11; U; Linux i686 (x86_64); en-US; rv:1.9a1) Gecko/20061204 GranParadiso/3.0a1',
			'Mozilla/5.0 (X11; U; Linux i686; en-US) AppleWebKit/532.9 (KHTML, like Gecko) Chrome/5.0.307.9 Safari/532.9',
			'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.7.9) Gecko/20050711 Firefox/1.0.5',
			'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.0.2) Gecko/20060308 Firefox/1.5.0.2',
			'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.0.3) Gecko/20060426 Firefox/1.5.0.3',
			'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.0.6) Gecko/20060808 Fedora/1.5.0.6-2.fc5 Firefox/1.5.0.6 pango-text',
			'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.1.2) Gecko/20070220 Firefox/2.0.0.2',
			'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.1.2) Gecko/20070221 SUSE/2.0.0.2-6.1 Firefox/2.0.0.2',
			'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.8.1) Gecko/20060601 Firefox/2.0 (Ubuntu-edgy)',
			'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.1.1) Gecko/20090716 Ubuntu/9.04 (jaunty) Shiretoko/3.5.1',
			'Mozilla/5.0 (X11; U; Linux x86_64; ru; rv:1.9.0.2) Gecko/2008092702 Gentoo Firefox/3.0.2',
			'Mozilla/5.0 (X11; U; Linux x86_64; ru; rv:1.9.0.4) Gecko/2008111611 Gentoo Iceweasel/3.0.4',
			'Mozilla/5.0 (X11; U; Linux x86_64; ru; rv:1.9.1.1) Gecko/20090730 Gentoo Firefox/3.5.1',
			'Mozilla/5.0 (compatible; MSIE 7.0; Windows NT 5.0)',
			'Mozilla/5.0 (compatible; MSIE 7.0; Windows NT 5.1)',
			'Mozilla/5.0 (compatible; MSIE 7.0; Windows NT 5.2)',
			'Mozilla/5.0 (compatible; MSIE 7.0; Windows NT 6.0)',
			'Mozilla/5.0 (compatible; MSIE 7.0; Windows NT 6.1)',
			'Opera/10.00 (Windows NT 6.0; U; en) Presto/2.2.0',
			'Opera/7.23 (Windows 98; U) [en]',
			'Opera/7.51 (Windows NT 5.0; U) [en]',
			'Opera/7.51 (Windows NT 5.1; U) [ru]',
			'Opera/7.51 (Windows NT 5.2; U) [ch]',
			'Opera/7.51 (Windows NT 6.0; U) [zw]',
			'Opera/7.51 (Windows NT 6.1; U) [ua]',
			'Opera/8.0 (X11; Linux i686; U; cs)',
			'Opera/8.51 (Windows NT 5.1; U; en)',
			'Opera/9.0 (Windows NT 5.1; U; en)',
			'Opera/9.00 (Nintendo Wii; U; ; 1309-9; en)',
			'Opera/9.00 (Wii; U; ; 1038-58; Wii Shop Channel/1.0; en)',
			'Opera/9.01 (X11; Linux i686; U; en)',
			'Opera/9.02 (Windows NT 5.1; U; en)',
			'Opera/9.10 (Windows NT 5.1; U; en)',
			'Opera/9.23 (Windows NT 5.1; U; ru)',
			'Opera/9.50 (Windows NT 5.1; U; ru)',
			'Opera/9.50 (Windows NT 6.0; U; en)',
			'Opera/9.60 (Windows NT 5.1; U; en) Presto/2.1.1',
			'Opera/9.80 (Windows NT 5.1; U; en) Presto/2.5.18 Version/10.50',
			'Opera/9.80 (Windows NT 5.1; U; ru) Presto/2.2.15 Version/10.20',
			'Opera/9.80 (Windows NT 6.1; U; ru) Presto/2.2.15 Version/10.00',
			'Opera/9.80 (Windows NT 6.1; U; ru) Presto/2.9.168 Version/11.51',
			'Opera/9.80 (X11; Linux x86_64; U; en) Presto/2.2.15 Version/10.10',
			'Opera/9.80 (X11; Linux x86_64; U; ru) Presto/2.2.15 Version/10.10',
			'StackRambler',
			'Yandex'
		);
		$rand_keys = array_rand($user_agents, 1);
		$this->setOption(CURLOPT_USERAGENT, $user_agents[$rand_keys]);
		return $this;
	}
}



































