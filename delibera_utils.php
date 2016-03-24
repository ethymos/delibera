<?php

/**
 * Return timestamp of parsered date or now if fail
 * @param string $data like: 22/01/1982
 * @param string $int return int or database format
 * @param string $onlastsecond append last day second to date (23:59:59)
 */
function delibera_tratar_data($data, $int = true, $onlastsecond = true)
{
	$data = trim($data);
	$dateTime = new DateTime();
	if($onlastsecond)
	{
		$dateTime = \DateTime::createFromFormat('d/m/Y H:i:s', $data." 23:59:59"); //TODO get wordpress format
	}
	else
	{
		$dateTime = \DateTime::createFromFormat('d/m/Y', $data);
	}
	if(is_object($dateTime))
	{
		return $dateTime->format( ($int ? 'U' : 'Y-m-d H:i:s'));
	}
	else
	{
		return false;
	}
}

/**
 *
 * Revemos acentos do texto
 * @param string $texto
 * @return string
 */
function delibera_tiracento($texto)
{
	$trocarIsso = 	array('à','á','â','ã','ä','å','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ü','ú','ÿ','À','Á','Â','Ã','Ä','Å','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ñ','Ò','Ó','Ô','Õ','Ö','Ù','Ü','Ú','Ÿ',);
	$porIsso = 		array('a','a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','y','A','A','A','A','A','A','C','E','E','E','E','I','I','I','I','N','O','O','O','O','O','U','U','U','Y',);
	$titletext = str_replace($trocarIsso, $porIsso, $texto);
	return $titletext;
}

function delibera_slug_under($label)
{
	$slug = delibera_tiracento($label);
	$slug = str_replace(array("'",'"','.',',',';','!'), '', $slug);
	$slug = str_replace(array(' ', '-'), '_', $slug);
	return strtolower($slug);
}

function is_pauta($post = false)
{
	return get_post_type($post) == 'pauta' ? true : false;
}

/**
 *
 *
 * @param string $text String to truncate.
 * @param integer $length Length of returned string, including ellipsis.
 * @param string $ending Ending to be appended to the trimmed string.
 * @param boolean $exact If false, $text will not be cut mid-word
 * @param boolean $considerHtml If true, HTML tags would be handled correctly
 * @return string Trimmed string.
 */

function truncate($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true) {
	if ($considerHtml) {
		// if the plain text is shorter than the maximum length, return the whole text
		if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
			return $text;
		}
		// splits all html-tags to scanable lines
		preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
		$total_length = strlen($ending);
		$open_tags = array();
		$truncate = '';
		foreach ($lines as $line_matchings) {
			// if there is any html-tag in this line, handle it and add it (uncounted) to the output
			if (!empty($line_matchings[1])) {
				// if it's an "empty element" with or without xhtml-conform closing slash
				if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
					// do nothing
				// if tag is a closing tag
				} else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
					// delete tag from $open_tags list
					$pos = array_search($tag_matchings[1], $open_tags);
					if ($pos !== false) {
					unset($open_tags[$pos]);
					}
				// if tag is an opening tag
				} else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
					// add tag to the beginning of $open_tags list
					array_unshift($open_tags, strtolower($tag_matchings[1]));
				}
				// add html-tag to $truncate'd text
				$truncate .= $line_matchings[1];
			}
			// calculate the length of the plain text part of the line; handle entities as one character
			$content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
			if ($total_length+$content_length> $length) {
				// the number of characters which are left
				$left = $length - $total_length;
				$entities_length = 0;
				// search for html entities
				if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
					// calculate the real length of all entities in the legal range
					foreach ($entities[0] as $entity) {
						if ($entity[1]+1-$entities_length <= $left) {
							$left--;
							$entities_length += strlen($entity[0]);
						} else {
							// no more characters left
							break;
						}
					}
				}
				$truncate .= substr($line_matchings[2], 0, $left+$entities_length);
				// maximum lenght is reached, so get off the loop
				break;
			} else {
				$truncate .= $line_matchings[2];
				$total_length += $content_length;
			}
			// if the maximum length is reached, get off the loop
			if($total_length>= $length) {
				break;
			}
		}
	} else {
		if (strlen($text) <= $length) {
			return $text;
		} else {
			$truncate = substr($text, 0, $length - strlen($ending));
		}
	}
	// if the words shouldn't be cut in the middle...
	if (!$exact) {
		// ...search the last occurance of a space...
		$spacepos = strrpos($truncate, ' ');
		if (isset($spacepos)) {
			// ...and cut the text in this position
			$truncate = substr($truncate, 0, $spacepos);
		}
	}
	// add the defined ending to the text
	$truncate .= $ending;
	if($considerHtml) {
		// close all unclosed html-tags
		foreach ($open_tags as $tag) {
			$truncate .= '</' . $tag . '>';
		}
	}
	return $truncate;
}

/**
 * Create a form table from an array of rows
 */
function delibera_form_table($rows) {
	$content = '<table class="form-table">';
	foreach ($rows as $row) {
		$content .= '<tr '.(array_key_exists('row-id', $row) ? 'id="'.$row['row-id'].'"' : '' ).' '.(array_key_exists('row-style', $row) ? 'style="'.$row['row-style'].'"' : '' ).' '.(array_key_exists('row-class', $row) ? 'class="'.$row['row-class'].'"' : '' ).' ><th valign="top" scrope="row">';

		if (isset($row['id']) && $row['id'] != '') {
			$content .= '<label for="'.$row['id'].'">'.$row['label'].'</label>';
		} else {
			$content .= $row['label'];
		}

		if (isset($row['desc']) && $row['desc'] != '') {
			$content .= '<br/><small>'.$row['desc'].'</small>';
		}

		$content .= '</th><td valign="top">';
		$content .= $row['content'];
		$content .= '</td></tr>';
	}
	$content .= '</table>';
	return $content;
}

/**
 *
 * Pega os ultimos conteúdos
 * @param string $tipo (option) 'pauta' ou 'comments', padrão 'pauta'
 * @param array $args (option) query padrão do post ou do comments
 * @param int $count (option) padrão 5
 */
function delibera_ultimas($tipo = 'pauta', $args = array(), $count = 5)
{
	switch($tipo)
	{
    case 'pauta':
        $filtro = array('orderby' => 'modified', 'order' => 'DESC', 'posts_per_page' => $count);
        $filtro = array_merge($filtro, $args);
        return delibera_get_pautas_em($filtro, false);
		break;
    case 'comments':
        $filtro = array('orderby' => 'comment_date_gmt', 'order' => 'DESC', 'number' => $count, 'post_type' => 'pauta');
        $filtro = array_merge($filtro, $args);
        return delibera_wp_get_comments($filtro);
		break;
	}
}

if(!function_exists('array_object_value_recursive'))
{
	/**
	 * Get all values from specific key in a multidimensional array of objects
	 * based on php.net http://php.net/manual/en/function.array-values.php
	 *
	 * @param $key string
	 * @param $arr array
	 * @return array
	 */
	function array_object_value_recursive($key, array $arr)
	{
		$val = array();
		array_walk_recursive($arr, function($v, $k) use($key, &$val)
		{
			if(is_object($v) && property_exists($v, $key) )
			{
				array_push($val, $v->$key);
			}
		});
		return $val;
	}
}

if(!function_exists('array_value_recursive'))
{
	/**
	 * Get all values from specific key in a multidimensional array
	 * based on php.net http://php.net/manual/en/function.array-values.php
	 * 
	 * @param $key string
	 * @param $arr array
	 * @return null|string|array
	 */
	function array_value_recursive($key, array $arr){
		$val = array();
		array_walk_recursive($arr, function($v, $k) use($key, &$val){
			if($k == $key) array_push($val, $v);
		});
			return count($val) > 1 ? $val : array_pop($val);
	}
}