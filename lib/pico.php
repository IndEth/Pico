<?php 

class Pico {
	const IN_EXT = '.md';

	function __construct()
	{
		// Get request url and script url
		$url = '';
		$request_url = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '';
		$script_url  = (isset($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : '';
			
		// Get our url path and trim the / of the left and the right
		if($request_url != $script_url) $url = trim(preg_replace('/'. str_replace('/', '\/', str_replace('index.php', '', $script_url)) .'/', '', $request_url, 1), '/');

		// Get the file path
		if($url) $file = strtolower(CONTENT_DIR . $url);
		else $file = CONTENT_DIR .'index';

		// Load the file
		if(is_dir($file)) $file = CONTENT_DIR . $url .'/index';
		
		$file .= self::IN_EXT;

		if(file_exists($file)) $content = file_get_contents($file);
		else $content = file_get_contents(CONTENT_DIR .'404'.self::IN_EXT);

		$meta = $this->read_file_meta($content);
                $vars = $this->read_file_vars($content);
		$content = preg_replace('#/\*.+?\*/#s', '', $content); // Remove comments and meta
		$content = $this->parse_content($content);

		// Load the settings
		$settings = $this->get_config();
		$env = array('autoescape' => false);
		if($settings['enable_cache']) $env['cache'] = CACHE_DIR;
		
		// Load the theme
		Twig_Autoloader::register();
		$loader = new Twig_Loader_Filesystem(THEMES_DIR . $settings['theme']);
		$twig = new Twig_Environment($loader, $env);
		echo $twig->render($vars['#template'], array(
			'config' => $settings,
			'base_dir' => rtrim(ROOT_DIR, '/'),
			'base_url' => $settings['base_url'],
			'theme_dir' => THEMES_DIR . $settings['theme'],
			'theme_url' => $settings['base_url'] .'/'. basename(THEMES_DIR) .'/'. $settings['theme'],
			'site_title' => $settings['site_title'],
			'meta' => $meta,
			'content' => $content,
                        'vars' => $vars,
		));
	}

	function parse_content($content)
	{
		$content = str_replace('%base_url%', $this->base_url(), $content);
		$content = Markdown($content);

		return $content;
	}

	function read_file_meta($content)
	{
                $config = $this->get_config();
		$headers = array(
			'title'       => 'Title',
			'description' => 'Description',
			'robots'      => 'Robots'
		);

	 	foreach ($headers as $field => $regex){
			if (preg_match('/^[ \t\/*#@]*' . preg_quote($regex, '/') . ':(.*)$/mi', $content, $match) && $match[1]){
                               $headers[ $field ] = trim(preg_replace("/\s*(?:\*\/|\?>).*/", '', $match[1]));
			}
                        else {
				$headers[ $field ] = $config[$field];//'';
			}
		}
                
		return $headers;
	}
        
        function read_file_vars($content)
	{
                $config = $this->get_config();
		$repcontent = $content;
                $vars = $config;
                while(preg_match('/^[ \t\/*#@]*\w+:(.*)$/mi', $repcontent, $match) && $match[1]){
                               $var =  lcfirst(str_replace(':'.$match[1], '', $match[0]));
                               $repcontent = str_replace($match[0], '', $repcontent);
                               $vars[ $var ] = trim(preg_replace("/\s*(?:\*\/|\?>).*/", '', $match[1]));
		}
                
                return $vars;
	}

	function get_config()
	{
		global $config;

		$defaults = array(
			'site_title' => 'Pico',
			'base_url' => $this->base_url(),
			'theme' => 'default',
			'enable_cache' => false,
                        'title' => '',
                        'description' => '',
                        'robots' => '',
                        '#template' => 'index.twig',
		);

		foreach($defaults as $key=>$val){
			if(isset($config[$key]) && $config[$key]) $defaults[$key] = $config[$key];
		}

		return $defaults;
	}

	function base_url()
	{
		global $config;
		if(isset($config['base_url']) && $config['base_url']) return $config['base_url'];

		$url = '';
		$request_url = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '';
		$script_url  = (isset($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : '';
		if($request_url != $script_url) $url = trim(preg_replace('/'. str_replace('/', '\/', str_replace('index.php', '', $script_url)) .'/', '', $request_url, 1), '/');

		$protocol = $this->get_protocol();
		return rtrim(str_replace($url, '', $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']), '/');
	}

	function get_protocol()
	{
		preg_match("|^HTTP[S]?|is",$_SERVER['SERVER_PROTOCOL'],$m);
		return strtolower($m[0]);
	}

}

?>
