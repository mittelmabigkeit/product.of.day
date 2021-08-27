<? if(!empty($arResult['PRODUCT'])): ?>
    <div class="day-product">
        <p class="day-product__title mobile">Двойной удар по цене</p>

        <div class="day-product-card">
            <a href="javascript:void(0)" class="day-product-card__img">
                <img src="<?= $arResult['PRODUCT']['PREVIEW'] ?>"
                     alt="Двойной удар по цене">
            </a>

            <div class="day-product-card__content">
                <p class="day-product__title">Двойной удар по цене</p>
                <p class="day-product-card__name"><?= $arResult['PRODUCT']['NAME'] ?></p>

                <div class="day-product-card__props">
                    <div class="day-product-card__prop">
                        <span class="day-product-card__prop-title">Вес</span>
                        <span class="day-product-card__prop-value"><?= $arResult['PRODUCT']['WEIGHT'] ?></span>
                    </div>
                    <div class="day-product-card__prop">
                        <span class="day-product-card__prop-title">Цена</span>
                        <? if(isset($arResult['PRODUCT']['OLD_PRICE'])): ?>
                            <span class="day-product-card__price day-product-card__price--old"><?= $arResult['PRODUCT']['OLD_PRICE'] * 2 ?>&nbsp;руб.</span>
                        <? endif; ?>
                        <span class="day-product-card__price day-product-card__price--new"><?= round($arResult['PRODUCT']['PRICE'] * 2, 0) ?>&nbsp;руб.</span>
                    </div>
                </div>
                <div class="day-product-txt">Скидка действует только при покупке двух товаров</div>
            </div>
        </div>

        <div class="day-product-timer">
            <span class="day-product-timer__label">До конца акции:</span>
            <div class="day-product-timer__wrapper">
                <span class="day-product-timer__value js-day-product-hours">21</span> :
                <span class="day-product-timer__value js-day-product-minutes">12</span> :
                <span class="day-product-timer__value js-day-product-seconds">06</span>
            </div>
            <div class="item_stock_cart">
                <a type="button" class="item_stock_cart_button day-product__btn"  data-id="<?=$arResult['PRODUCT']['ID']?>" data-quantity="2" data-iblockid="<?= \App\Config::params('OFFERS_IBLOCK') ?>" href="<?= $APPLICATION->GetCurPage() . '?action=ADD2BASKET&id=' . $arResult['PRODUCT']['ID'] ?>" onclick="fbq('track', 'AddToCart');" title="Добавить в корзину">
                    <div class="day-product__btn_text">
                        <i class="ico-cart"></i>
                        Купить
                    </div>
                </a>
            </div>
        </div>
    </div>

    <script>
        let lostTime = <?= $arResult['PRODUCT']['TTL'] ?>;

        const interval = setInterval(function() {

            let lostSeconds = lostTime;

            const lostHours = getHours(lostSeconds);
            lostSeconds = lostSeconds - (lostHours * 3600);

            const lostMinutes = getMinutes(lostSeconds);
            lostSeconds = lostSeconds - (lostMinutes * 60);

            document.querySelector('.js-day-product-hours').innerHTML = lostHours < 10 ? '0' + lostHours : lostHours;
            document.querySelector('.js-day-product-minutes').innerHTML = lostMinutes < 10 ? '0' + lostMinutes : lostMinutes;
            document.querySelector('.js-day-product-seconds').innerHTML = lostSeconds < 10 ? '0' + lostSeconds : lostSeconds;

            lostTime--;

            if (lostTime < 0) {
                clearInterval(interval)
            }
        }, 1000);

        function getHours(seconds) {
            return Math.floor(seconds / 3600);
        }

        function getMinutes(seconds) {
            return Math.floor(seconds / 60);
        }

		$('.day-product__btn').on('click',function(){
            
            if (typeof yaCounter20037928 !== 'undefined') {
                yaCounter20037928.reachGoal('tovar_dnya');
            }
        });
    </script>
<? endif; ?>