#! /bin/bash

find "$@" -type f -print0 | xargs -0 sed -i -r \
  -e 's/Drupal\\wmcontroller\\Entity\\(AbstractPresenter|HasPresenterInterface|PresenterInterface)/Drupal\\wmpresenter\\Entity\\\1/g' \
  -e 's/Drupal\\wmcontroller\\Twig\\Extension\\PresenterExtension/Drupal\\wmpresenter\\Twig\\Extension\\PresenterExtension/g' \
  -e 's/Drupal\\wmcontroller\\Service\\(PresenterFactory(Interface)?)/Drupal\\wmpresenter\\\1/g' \
  -e 's/wmcontroller\.presenter\.(.+)/wmpresenter.\1/g' \
  -e 's/wmcontroller\.presenter/wmpresenter.presenter/g' \
  -e 's/Drupal\\wmcontroller\\Service\\Cache\\(.+)/Drupal\\wmpage_cache\\\1/g' \
  -e 's/wmcontroller\.cache\.(.+)/wmpage_cache.\1/g'
