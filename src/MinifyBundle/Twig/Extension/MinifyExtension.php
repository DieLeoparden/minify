<?php
/**
 * Pimcore MinifyBundle
 * Copyright (c) Die Leoparden e.K.
 */

namespace MinifyBundle\Twig\Extension;

use lessc;
use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;
use Twig\Markup;

final class MinifyExtension extends \Twig_Extension
{
    /**
     * @var array
     */
    private $minifyData;

    /**
     * @var
     */
    private $lessVariables;

    /**
     * @var
     */
    private $async = false;

    /**
     * @var
     */
    private $media = 'all';

    /**
     * @var
     */
    private $output = 'output';

    /**
     * @return array|\Twig_Function[]
     */
    public function getFunctions()
    {
        return [
            new \Twig_Function('minify', [$this, 'getMinifyLinks'], [
                'is_safe' => ['html'],
            ]),
        ];
    }

    /**
     * @param array $minifyData
     */
    public function setMinifyData(array $minifyData)
    {
        $this->minifyData = $minifyData;
    }

    /**
     * @return mixed
     */
    public function getMinifyData()
    {
        return $this->minifyData;
    }

    /**
     * @param $lessVariables
     */
    public function setLessVariables($lessVariables)
    {
        $this->lessVariables = $lessVariables;
    }

    /**
     * @return mixed
     */
    public function getLessVariables()
    {
        return $this->lessVariables;
    }

    /**
     * @param $async
     */
    public function setAsync($async)
    {
        $this->async = $async;
    }

    /**
     * @return mixed
     */
    public function getAsync()
    {
        return $this->async;
    }

    /**
     * @param $media
     */
    public function setMedia($media)
    {
        $this->media = $media;
    }

    /**
     * @return mixed
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * @param $output_name
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param $minifyData
     * @param array  $lessVariables
     * @param bool   $async
     * @param string $media
     * @param string $output_name
     *
     * @throws \Exception
     *
     * @return bool|Markup|void
     */
    public function getMinifyLinks(
        $minifyData,
        $config = []
    ) {
        if (!is_array($minifyData)) {
            return;
        }

        // set minify data
        $this->setMinifyData($minifyData);

        // set config data
        if (is_array($config)) {
            foreach ($config as $key => $value) {
                if (method_exists($this, 'set' . lcfirst($key))) {
                    $setter = 'set' . lcfirst($key);
                    $this->$setter($value);
                }
            }
        }

        foreach ($minifyData as $data) {
            $data = trim($data, '/');

            // external data
            if (preg_match('#^(http|https):\/\/#', $data)) {
                $output_array['external'][$this->getDataType($data)][] = $data;
            } else {
                // internal files
                if (file_exists(PIMCORE_WEB_ROOT . '/' . $data) &&
                    filetype(PIMCORE_WEB_ROOT . '/' . $data) == 'file') {
                    if ($this->getDataType($data)) {
                        $output_array['internal'][$this->getDataType($data)][] = $data;
                    }
                }
            }
        }

        // build output array
        $output_array = $this->buildMinifyOutput($output_array);

        if (is_array($output_array)) {
            $minifyOutput = '';
            foreach ($output_array as $type => $output) {
                if ($type == 'internal') {
                    foreach ($output as $key => $data) {
                        if ($key == 'css') {
                            $minifyOutput .= '<link rel="stylesheet" type="text/css" href="' . $data['file'] . '?' . $data['filemtime'] . '" media="' . $this->getMedia() . '" >';
                        } elseif ($key == 'js') {
                            $minifyOutput .= '<script type="text/javascript" src="' . $data['file'] . '?' . $data['filemtime'] . '"' . ($this->getAsync() ? ' async' : '') . '></script>';
                        }
                    }
                } elseif ($type == 'external') {
                    foreach ($output as $key => $data) {
                        if ($key == 'css') {
                            $minifyOutput .= '<link rel="stylesheet" type="text/css" href="' . $data['file'] . '" media="' . $this->getMedia() . '" >';
                        } elseif ($key == 'js') {
                            $minifyOutput .= '<script type="text/javascript" src="' . $data['file'] . '" ' . ($this->getAsync()) ? 'async' : '' . '></script>';
                        }
                    }
                }
            }

            return $minifyOutput;
        }

        return false;
    }

    /**
     * @return string
     */
    private function getOutputPath()
    {
        return dirname(__DIR__, 2) . '/Resources/public';
    }

    /**
     * @param $data
     * @param string $less
     *
     * @return bool|string|void
     */
    private function getDataType($data, $less = 'css')
    {
        if (!$data || !is_string($data)) {
            return;
        }

        preg_match('#\.([a-z0-9]+)$#i', $data, $tmp);
        switch ($tmp[0]) {
            case '.css':
                return 'css';
                break;
            case '.less':
                return $less;
                break;
            case '.js':
                return 'js';
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * @param $data
     *
     * @throws \Exception
     *
     * @return array|void
     */
    private function buildMinifyOutput($data)
    {
        if (!is_array($data)) {
            return;
        }

        $output = [];

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'internal':
                    // build stylesheet minify
                    if (isset($value['css'])) {
                        if (PIMCORE_DEBUG && file_exists($this->getOutputPath() . '/css/' . $this->getOutput() . '.css')) {
                            unlink($this->getOutputPath() . '/css/' . $this->getOutput() . '.css');
                        }

                        if (!file_exists($this->getOutputPath() . '/css/' . $this->getOutput() . '.css')) {
                            file_put_contents($this->getOutputPath() . '/css/' . $this->getOutput() . '.css', '');
                            // build minifier object
                            $minifier = new CSS();
                            foreach ($value['css'] as $stylesheet) {
                                if ($this->getDataType($stylesheet, 'less') == 'less') {
                                    try {
                                        // set less variables to minify
                                        $less = new lessc();
                                        if (is_array($this->getLessVariables())) {
                                            $less->setVariables($this->getLessVariables());
                                        }
                                        // compile file
                                        $css = $less->compileFile($stylesheet);
                                        // add stylesheet to minifyer
                                        $minifier->add($css);
                                    } catch (\Exception $e) {
                                        throw new \Exception('Wrong Less Format');
                                    }
                                } else {
                                    // add stylesheet to minifyer
                                    $minifier->add($stylesheet);
                                }
                            }
                            // build file
                            $minifier->minify($this->getOutputPath() . '/css/' . $this->getOutput() . '.css');
                        }
                        // build output array
                        $output['internal']['css'] = [
                            'filemtime' => filemtime($this->getOutputPath() . '/css/' . $this->getOutput() . '.css'),
                            'file' => '/bundles/minify/css/' . $this->getOutput() . '.css',
                        ];
                    }
                    // build javascript minify
                    if (isset($value['js'])) {
                        if (PIMCORE_DEBUG && file_exists($this->getOutputPath() . '/js/' . $this->getOutput() . '.js')) {
                            unlink($this->getOutputPath() . '/js/' . $this->getOutput() . '.js');
                        }

                        if (!file_exists($this->getOutputPath() . '/js/' . $this->getOutput() . '.js')) {
                            file_put_contents($this->getOutputPath() . '/js/' . $this->getOutput() . '.js', '');
                            // build minifier object
                            $minifier = new JS();
                            foreach ($value['js'] as $javascript) {
                                $minifier->add($javascript);
                            }
                            // build file
                            $minifier->minify($this->getOutputPath() . '/js/' . $this->getOutput() . '.js');
                        }

                        // build output array
                        $output['internal']['js'] = [
                            'filemtime' => filemtime($this->getOutputPath() . '/js/' . $this->getOutput() . '.js'),
                            'file' => '/bundles/minify/js/' . $this->getOutput() . '.js',
                        ];
                    }

                    break;
                case 'external':
                    $output['external'] = $value;
                    break;
            }
        }

        return $output;
    }
}
