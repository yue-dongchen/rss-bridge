<?php

class NordbayernBridge extends BridgeAbstract {

	const MAINTAINER = 'schabi.org';
	const NAME = 'Nordbayern';
	const CACHE_TIMEOUT = 3600;
	const URI = 'https://www.nordbayern.de';
	const DESCRIPTION = 'Bridge for Bavarian regional news site nordbayern.de';
	const PARAMETERS = array( array(
		'region' => array(
			'name' => 'region',
			'type' => 'list',
			'exampleValue' => 'Nürnberg',
			'title' => 'Select a region',
			'values' => array(
				'Nürnberg' => 'nuernberg',
				'Fürth' => 'fuerth',
				'Erlangen' => 'erlangen',
				'Altdorf' => 'altdorf',
				'Ansbach' => 'ansbach',
				'Bad Windsheim' => 'bad-windsheim',
				'Bamberg' => 'bamberg',
				'Dinkelsbühl/Feuchtwangen' => 'dinkelsbuehl-feuchtwangen',
				'Feucht' => 'feucht',
				'Forchheim' => 'forchheim',
				'Gunzenhausen' => 'gunzenhausen',
				'Hersbruck' => 'hersbruck',
				'Herzogenaurach' => 'herzogenaurach',
				'Hilpoltstein' => 'hilpoltstein',
				'Höchstadt' => 'hoechstadt',
				'Lauf' => 'lauf',
				'Neumarkt' => 'neumarkt',
				'Neustadt/Aisch' => 'neustadt-aisch',
				'Pegnitz' => 'pegnitz',
				'Roth' => 'roth',
				'Rothenburg o.d.T.' => 'rothenburg-o-d-t',
				'Treuchtlingen' => 'treuchtlingen',
				'Weißenburg' => 'weissenburg'
			)
		),
		'policeReports' => array(
			'name' => 'Police Reports',
			'type' => 'checkbox',
			'exampleValue' => 'checked',
			'title' => 'Include Police Reports',
		)
	));

	private function getValidImage($picture) {
		$img = $picture->find('img', 0);
		if ($img) {
			$imgUrl = $img->src;
			if(($imgUrl != '/img/nb/logo-vnp.png')  &&
				($imgUrl != '/img/nn/logo-vnp.png') &&
				($imgUrl != '/img/nb/logo-nuernberger-nachrichten.png') &&
				($imgUrl != '/img/nb/logo-nordbayern.png') &&
				($imgUrl != '/img/nn/logo-nuernberger-nachrichten.png') &&
				($imgUrl != '/img/nb/logo-erlanger-nachrichten.png')) {
				return '<br><img src="' . $imgUrl . '">';
			}
		}
		return '';
	}

	private function getUseFullContent($rawContent) {
		$content = '';
		foreach($rawContent->children as $element) {
			if($element->tag === 'p' || $element->tag === 'h3') {
				$content .= $element;
			} else if($element->tag === 'main') {
				$content .= self::getUseFullContent($element->find('article', 0));
			} else if($element->tag === 'header') {
				$content .= self::getUseFullContent($element);
			} else if($element->tag === 'div' &&
				!str_contains($element->class, 'article__infobox') &&
				!str_contains($element->class, 'authorinfo')) {
				$content .= self::getUseFullContent($element);
			} else if($element->tag == 'section' &&
				(str_contains($element->class, 'article__richtext') ||
					str_contains($element->class, 'article__context'))) {
				$content .= self::getUseFullContent($element);
			} else if($element->tag == 'picture') {
				$content .= self::getValidImage($element);
			}
		}
		return $content;
	}

	private function handleArticle($link) {
		$item = array();
		$article = getSimpleHTMLDOM($link);
		defaultLinkTo($article, self::URI);
		$content = $article->find('article[id=article]', 0);
		$item['uri'] = $link;

		$author = $article->find('[id="openAuthor"]', 0);
		if ($author) {
			$item['author'] = $author->plaintext;
		}

		$createdAt = $article->find('[class=article__release]', 0);
		if ($createdAt) {
			$item['timestamp'] = strtotime(str_replace('Uhr', '', $createdAt->plaintext));
		}

		if ($article->find('h2', 0) == null) {
			$item['title'] = $article->find('h3', 0)->innertext;
		} else {
			$item['title'] = $article->find('h2', 0)->innertext;
		}
		$item['content'] = '';

		if ($article->find('section[class*=article__richtext]', 0) == null) {
			$content = $article->find('div[class*=modul__teaser]', 0)
						   ->find('p', 0);
			$item['content'] .= $content;
		} else {
			//$content = $article->find('section[class*=article__richtext]', 0)
			//			   ->find('div', 0)->find('div', 0);
			$content = $article->find('article', 0);
			$item['content'] .= self::getUseFullContent($content);
		}

		// exclude police reports if desired
		if($this->getInput('policeReports') ||
			!str_contains($item['content'], 'Hier geht es zu allen aktuellen Polizeimeldungen.')) {
			$this->items[] = $item;
		}

		$article->clear();
	}

	private function handleNewsblock($listSite) {
		$main = $listSite->find('main', 0);
		foreach($main->find('article') as $article) {
			$url = $article->find('a', 0)->href;
			$url = urljoin(self::URI, $url);
			self::handleArticle($url);
		}
	}

	public function collectData() {
		$region = $this->getInput('region');
		if($region === 'rothenburg-o-d-t') {
			$region = 'rothenburg-ob-der-tauber';
		}
		$url = self::URI . '/region/' . $region;
		$listSite = getSimpleHTMLDOM($url);

		self::handleNewsblock($listSite);
	}
}
