<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header('Content-Type: application/json');

\Bitrix\Main\Loader::includeModule('iblock');
const CACHE_TIME = 3600 * 24;

$request = \Bitrix\Main\Context::getCurrent()->getRequest();
$pageSize = empty($request->getQuery('pageSize')) ? 10 : (Integer) $request->getQuery('pageSize');
$page = empty($request->getQuery('page')) ? 1 : (Integer) $request->getQuery('page');

$resultJsonItems = []; 

// Ключи и имена авторов
$authors = [];
$resultAuthors = \Bitrix\Iblock\Elements\ElementAuthorsTable::getList(array(
   'select' => ['*'], 
    'limit' => 1000, 
    'offset' => 0,
    'cache' => array(
        'ttl' => CACHE_TIME,
        'cache_joins' => true,
    )
));

$arItems = $resultAuthors->fetchAll();
foreach ($arItems as $item) {
	$authors[$item['ID']] = $item['NAME'];
}

// Основной запрос к новостям
$resultNews = \Bitrix\Iblock\Elements\ElementNewsTable::getList(array(
   'select' => [
		'*', 
		'URL' => 'IBLOCK.DETAIL_PAGE_URL',
		'IBLOCK_SECTION',
		'AUTHOR'
		], 
    'offset' => $pageSize * ($page - 1),
    'limit' => $pageSize,
    'cache' => array(
        'ttl' => CACHE_TIME,
        'cache_joins' => true,
    )
));

$arItems = $resultNews->fetchAll();
foreach ($arItems as $item) {
	$item['URL'] = CIBlock::ReplaceDetailUrl($item["URL"], $item, true, 'E' );

	$resultJsonItems[] = [
		'id' => $item['ID'],
		'url' => $item['URL'],
		'image' => CFile::GetPath($item["PREVIEW_PICTURE"]),
		'name' => $item['NAME'],
		'sectionName' => $item['IBLOCK_ELEMENT_IBLOCK_SECTION_NAME'],
		'date' => $item['DATE_CREATE']->format("d F y H:i"), 
		'author' => $authors[(Integer)$item['IBLOCK_ELEMENTS_ELEMENT_NEWS_AUTHOR_VALUE']],
		'tags' => $item['TAGS']
	];
}
	
// Вывод результа в json
$strJson = \Bitrix\Main\Web\Json::encode($resultJsonItems);
echo $strJson;	
	
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
