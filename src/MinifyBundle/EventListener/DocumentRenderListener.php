<?php

namespace MinifyBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use voku\helper\HtmlMin;

class DocumentRenderListener
{
    protected $container;

    public function __construct($container) // this is @service_container
    {
        $this->container = $container;
    }

    public function onRender(GetResponseForControllerResultEvent $event)
    {
        /* @var Template $template */
        $request = $event->getRequest();
        $template = $request->attributes->get('_template');

        if (!$template instanceof Template) {
            return;
        }

        $parameters = $event->getControllerResult();
        $owner = $template->getOwner();
        list($controller, $action) = $owner;

        // when the annotation declares no default vars and the action returns
        // null, all action method arguments are used as default vars
        if (null === $parameters) {
            $parameters = $this->resolveDefaultParameters($request, $template, $controller, $action);
        }

        // attempt to render the actual response
        $templating = $this->container->get('templating');

        if (!$template->isStreamable() and !$request->attributes->get('_editmode')) {
            $template_content = $templating->renderResponse($template->getTemplate(), $parameters)->getContent();

            $htmlMin = new HtmlMin();
            $htmlMin->doOptimizeViaHtmlDomParser();               // optimize html via "HtmlDomParser()"
            $htmlMin->doRemoveComments();                         // remove default HTML comments (depends on "doOptimizeViaHtmlDomParser(true)")
            $htmlMin->doOptimizeAttributes();                     // optimize html attributes (depends on "doOptimizeViaHtmlDomParser(true)")
            $htmlMin->doRemoveEmptyAttributes();                  // remove some empty attributes (depends on "doOptimizeAttributes(true)")
            $htmlMin->doRemoveValueFromEmptyInput();              // remove 'value=""' from empty <input> (depends on "doOptimizeAttributes(true)")
            $htmlMin->doSortCssClassNames();                      // sort css-class-names, for better gzip results (depends on "doOptimizeAttributes(true)")
            $htmlMin->doSortHtmlAttributes();                     // sort html-attributes, for better gzip results (depends on "doOptimizeAttributes(true)")

            $event->setResponse($templating->renderResponse($template->getTemplate(), $parameters)->setContent($htmlMin->minify($template_content)));
        }

        // make sure the owner (controller+dependencies) is not cached or stored elsewhere
        $template->setOwner(array());

    }
}
