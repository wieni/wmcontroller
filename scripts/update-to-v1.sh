#! /bin/bash

find "$@" -type f -print0 | xargs -0 sed -i -r \
  -e 's/Drupal\\wmcontroller\\Entity\\(AbstractPresenter|HasPresenterInterface|PresenterInterface)/Drupal\\wmpresenter\\Entity\\\1/g' \
  -e 's/Drupal\\wmcontroller\\Entity\\Cache/Drupal\\wmpage_cache\\Cache/g' \
  -e 's/Drupal\\wmcontroller\\Exception\\NoSuchCacheEntryException/Drupal\\wmpage_cache\\Exception\\NoSuchCacheEntryException/g' \
  -e 's/Drupal\\wmcontroller\\Service\\Cache\\(.+)/Drupal\\wmpage_cache\\\1/g' \
  -e 's/Drupal\\wmcontroller\\Service\\Factory/Drupal\\wmpage_cache\\ServiceFactory/g' \
  -e 's/Drupal\\wmcontroller\\Service\\(PresenterFactory(Interface)?)/Drupal\\wmpresenter\\\1/g' \
  -e 's/Drupal\\wmcontroller\\Twig\\Extension\\PresenterExtension/Drupal\\wmpresenter\\Twig\\Extension\\PresenterExtension/g' \
  -e 's/Drupal\\wmcontroller\\ViewBuilder\\ViewBuilder/Drupal\\wmtwig\\ViewBuilder/g' \
  -e 's/wmcontroller\.presenter\.(.+)/wmpresenter.\1/g' \
  -e 's/wmcontroller\.presenter/wmpresenter.presenter/g' \
  -e 's/wmcontroller\.cache\.(.+)/wmpage_cache.\1/g' \
  -e 's/wmcontroller\.viewbuilder/wmtwig\.viewbuilder/g' \
  -e 's/wmcontroller\.settings/wmtwig\.settings/g'
