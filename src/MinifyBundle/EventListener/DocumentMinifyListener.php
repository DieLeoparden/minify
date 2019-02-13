<?php
/**
 * Pimcore MinifyBundle
 * Copyright (c) Die Leoparden e.K.
 */

namespace MinifyBundle\EventListener;

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PreviewRequestTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\ResponseInjectionTrait;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Tool;
use voku\helper\HtmlMin;

class DocumentMinifyListener
{
    use PimcoreContextAwareTrait;
    use PreviewRequestTrait;
    use ResponseInjectionTrait;

    /**
     * @param $event
     */
    public function onKernelResponse($event)
    {
        $request = $event->getRequest();
        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if (!Tool::useFrontendOutputFilters()) {
            return;
        }

        if ($this->isPreviewRequest($request)) {
            return;
        }

        $response = $event->getResponse();
        if (!$this->isHtmlResponse($response)) {
            return;
        }

        $htmlMin = new HtmlMin();
        $htmlMin->doOptimizeViaHtmlDomParser();               // optimize html via "HtmlDomParser()"
        $htmlMin->doRemoveComments();                         // remove default HTML comments (depends on "doOptimizeViaHtmlDomParser(true)")
        $htmlMin->doOptimizeAttributes();                     // optimize html attributes (depends on "doOptimizeViaHtmlDomParser(true)")
        $htmlMin->doRemoveEmptyAttributes();                  // remove some empty attributes (depends on "doOptimizeAttributes(true)")
        $htmlMin->doRemoveValueFromEmptyInput();              // remove 'value=""' from empty <input> (depends on "doOptimizeAttributes(true)")
        $htmlMin->doSortCssClassNames();                      // sort css-class-names, for better gzip results (depends on "doOptimizeAttributes(true)")
        $htmlMin->doSortHtmlAttributes();                     // sort html-attributes, for better gzip results (depends on "doOptimizeAttributes(true)")

        $response->setContent($htmlMin->minify($response->getContent()));
    }
}
