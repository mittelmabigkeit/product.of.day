<?php

use \Bitrix\Main\Loader;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\ORM\Query;

class ProductOfDay extends CBitrixComponent
{
    private function chekModules()
    {
        if (Loader::includeModule('iblock')) {
            return true;
        }
        return false;
    }

    protected function getProduct()
    {
        global $APPLICATION;

        $curPath = $this->arParams['SECTION_PATH'];
        $parentSection = false;

        if(strstr($curPath, 'koshki'))
        {
            $parentSection = $this->arParams['CAT_SECTION'];
        }elseif(strstr($curPath, 'sobaki'))
        {
            $parentSection = $this->arParams['DOG_SECTION'];
        }

        if($parentSection)
        {
            $now = new \Bitrix\Main\Type\DateTime();


            \Bitrix\Main\Application::getConnection()->startTracker();

            $arCurDayProducts = [];
            $rsDayProducts = CIBlockElement::GetList(
                ['ID' => 'ASC'],
                [
                    'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                    'ACTIVE' => 'Y',
                    'PROPERTY_URL' => $APPLICATION->GetCurPage(),
                    '<=DATE_ACTIVE_FROM' => date('d.m.Y H:i:s'),
                    '>=DATE_ACTIVE_TO' => date('d.m.Y H:i:s')
                ],
                false,
                ['nTopCount' => 1],
                [
                    'ID',
                    'NAME',
                    'ACTIVE_TO',
                    'PROPERTY_URL'
                ]
            );

            while ($arDayProduct = $rsDayProducts->Fetch()) {
                $arDayProduct['ACTIVE_TO'] = new \Bitrix\Main\Type\DateTime($arDayProduct['ACTIVE_TO']);
                $arCurDayProducts = $arDayProduct;
            }

            $product = ElementTable::query()
                ->addSelect('NAME')
                ->addSelect('ID')
                ->addSelect('ACTIVE_TO')
                ->addSelect('ACTIVE_FROM')
                ->addSelect('CODE')
                ->where('IBLOCK_ID', '=', $this->arParams['IBLOCK_ID'])
                ->where('IBLOCK_SECTION_ID', $parentSection)
                ->where('ACTIVE_TO', '>=', $now)
                ->where('ACTIVE_FROM', '<=', $now)
                ->where('WF_STATUS_ID', 1)
                ->whereNull('WF_PARENT_ELEMENT_ID')
                ->exec()
                ->fetchAll();

            shuffle($product);
            $product = reset($product);

            if (!empty($arCurDayProducts)) {
                $product = $arCurDayProducts;
            }

            if ($_GET['reset_dey_products']) {
                $product = 1;
            }

            if(is_array($product))
            {
                $rsOffers = CIBlockElement::GetList(
                    [],
                    [
                        'IBLOCK_ID' => 39,
                        'ID' => $product['NAME']
                    ],
                    false,
                    false,
                    [
                        'NAME',
                        'PROPERTY_OLD_PRICE',
                        'ID',
                        'PROPERTY_IS_OFFER_DAYS'
                    ]
                );

                $curPrice = CPrice::GetBasePrice($product['NAME']);

                if($arOffer = $rsOffers->GetNext())
                {
                    if (empty($arOffer['PROPERTY_IS_OFFER_DAYS_VALUE'])) {
                        \CIBlockElement::SetPropertyValues(
                            $arOffer['ID'],
                            \App\Config::params('OFFERS_IBLOCK'),
                            466,
                            'IS_OFFER_DAYS'
                        );
                    }

                    $price = $curPrice['PRICE'] * 0.9;
                    $oldPrice = $curPrice['PRICE'];

                    $parentProduct = CCatalogSku::GetProductInfo($product['NAME']);
                    if(is_array($parentProduct))
                    {
                        $parentDB = CIBlockElement::GetList(
                            [],
                            [
                                'IBLOCK_ID' => 38,
                                'ID' => $parentProduct['ID']
                            ],
                            false,
                            false,
                            [
                                'ID',
                                'NAME',
                                'DETAIL_PICTURE'
                            ]
                        );

                        if ($parent = $parentDB->GetNext()) {
                            $ttl = $product['ACTIVE_TO']->getTimestamp() - $now->getTimestamp();
                            $this->arResult['PRODUCT'] = [
                                'PARENT_ID' => $parent['ID'],
                                'ID' => $product['NAME'],
                                'NAME' => $parent['NAME'],
                                'PREVIEW' => CFile::GetPath($parent["DETAIL_PICTURE"]),
                                'WEIGHT' => $arOffer['NAME'],
                                'PRICE' => $price,
                                'OLD_PRICE' => $oldPrice,
                                'TTL' => $ttl
                            ];
                        }
                    }
                }
            } else {
                $res = CIBlockElement::GetList(
                    ['PROPERTY_URL' => 'DESC'],
                    [
                        "IBLOCK_ID" => $this->arParams['IBLOCK_ID'],
                        "ACTIVE" => "Y",
                        'IBLOCK_SECTION_ID' => $parentSection
                    ],
                    false,
                    false,
                    [
                        'ID',
                        'ACTIVE_FROM',
                        'ACTIVE_TO',
                        'PROPERTY_URL',
                        'IBLOCK_SECTION_ID',
                    ]
                );

                $from = new \Bitrix\Main\Type\DateTime();

                $sOldSectionUrl = '';

                while ($ob = $res->Fetch()) {
                    $arFields = $ob;

                    if ($sOldSectionUrl != $arFields['PROPERTY_URL_VALUE']) {
                        $from = new \Bitrix\Main\Type\DateTime();
                    }

                    $el = new CIBlockElement;
                    $to = clone $from;

                    $arLoadProductArray = array(
                        "ACTIVE_FROM" => $from,
                        'ACTIVE_TO' => $to->add('2 days'),
                        'IBLOCK_SECTION_ID' => $parentSection
                    );

                    $el->Update($arFields['ID'], $arLoadProductArray);

                    $from->add('2 days');

                    $sOldSectionUrl = $arFields['PROPERTY_URL_VALUE'];
                }
            }

            $productOld = ElementTable::query()
                ->addSelect('NAME')
                ->addSelect('ID')
                ->addSelect('ACTIVE_TO')
                ->addSelect('ACTIVE_FROM')
                ->addSelect('CODE')
                ->where('IBLOCK_ID', '=', $this->arParams['IBLOCK_ID'])
                ->where('IBLOCK_SECTION_ID', $parentSection)
                ->where('ACTIVE_TO', '<', $now)
                ->where('CODE', '=', '1')
                ->where('WF_STATUS_ID', 1)
                ->whereNull('WF_PARENT_ELEMENT_ID')
                ->fetch();

            if(is_array($productOld))
            {
                $price = CPrice::GetBasePrice($productOld['NAME']);

                $rsOldOffers = CIBlockElement::GetList(
                    [],
                    [
                        'IBLOCK_ID' => 39,
                        'ID' => $productOld['NAME']
                    ],
                    false,
                    false,
                    [
                        'NAME',
                        'PROPERTY_OLD_PRICE',
                        'ID'
                    ]
                );

                if($arOldOffer = $rsOldOffers->GetNext())
                {
                    if(isset($arOldOffer['PROPERTY_OLD_PRICE_VALUE']))
                    {
                        $arPriceFields = Array(
                            "PRODUCT_ID" => $arOldOffer['ID'],
                            "CATALOG_GROUP_ID" => 1,
                            "PRICE" => $arOldOffer['PROPERTY_OLD_PRICE_VALUE'],
                            "CURRENCY" => 'RUB',
                            "QUANTITY_FROM" => false,
                            "QUANTITY_TO" => false,
                        );

                        CIBlockElement::SetPropertyValueCode($arOldOffer['ID'], 'OLD_PRICE', false);
                        CPrice::Update($price["ID"], $arPriceFields);

                        $arLoadProductArray = Array(
                            "CODE"    => false,
                            'IBLOCK_SECTION_ID' => $parentSection
                        );

                        $el = new CIBlockElement;
                        $res = $el->Update($productOld['ID'], $arLoadProductArray);
                    }

                }
            }
        }
    }

    public function executeComponent()
    {
        $this->chekModules();
        $this->getProduct();

        $this->includeComponentTemplate();
    }
}