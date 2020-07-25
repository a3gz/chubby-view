<?php

/**
 * @copyright Copyright (c) Alejandro Arbiza
 * @license   http://www.roetal.com/license/mit (MIT License)
 */

namespace Chubby\View;

use A3gZ\PhpDomParser\HtmlDomParser;

class Template {
  protected $basePath;

  protected $components = [];

  protected $renderedComponents = [];

  /**
   * $data
   *
   * @var array $data Array of (key => value) pars having used in the view.
   */
  protected $data = [];

  protected $helpers = [];

  protected $template;

  /**
   * $styles
   *
   * @var array Code to be injected into the page head
   */
  protected $styles = [];

  /**
   * $scripts
   *
   * @var array Scripts to be injected in the DOM
   */
  protected $scripts = [];

  /**
   * $placeholders
   *
   * @var array Pre-defined custom placeholders
   */
  protected $placeholders = [
    'chubby-scripts' => [],
    'chubby-styles' => [],
  ];

  public function __construct($basePath = null) {
    $this->basePath = rtrim($basePath, '/');
    // Merge components from the child class
    $class = get_called_class();
    while ($class = get_parent_class($class)) {
      $components = get_class_vars($class)['components'];
      $clean = [];
      foreach ($components as $name => $path) {
        $path = realpath($path);
        if (!is_readable($path)) {
          throw new \Exception("The component {$name} was not found at {$path}.");
        }
        $clean[$name] = $this->getPreparedPath($path);
      }
      $this->components += $clean;
    }
    // Complete the template file name
    $this->template = $this->getPreparedPath($this->template);
  }

  public function __call($key, $args) {
    if (isset($this->helpers[$key]) && is_callable($this->helpers[$key])) {
      return call_user_func_array($this->helpers[$key], $args);
    }
  }

  /**
   * Getter to allow views to access the data passed in for them.
   *
   * @param string $key The variable name.
   * @return mixed The given value.
   */
  public function __get($key) {
    if (isset($this->data[$key])) {
      return $this->data[$key];
    }
    if (isset($this->helpers[$key])) {
      return $this->helpers[$key];
    }
  }

  public function __isset($key) {
    return isset($this->data[$key]);
  }

  public function addHelper($key, $value) {
    $key = str_replace(' ', '_', $key);
    $this->helpers[$key] = $value;
    return $this;
  }

  /**
   * Register a component in the template so later we can render it via the
   * name as in $this->render('nice-name').
   *
   * @param string $name
   * @param string $path
   * @return $this
   */
  public function define($name, $path) {
    if (!preg_match('#^.+(.php|.html)$#', $path)) {
      $path .= '.php';
    }
    $prepared = $this->getPreparedPath($path);
    if (!is_readable($prepared)) {
      throw new \Exception("The component `{$name}` could not by found at `{$path}`");
    }
    $this->components[$name] = $path;
    return $this;
  }

  public function defineRendered($name, $content) {
    $this->renderedComponents[$name] = $content;
    return $this;
  }

  protected function getPreparedPath($path) {
    if (substr($path, 0, 1) != '/') {
      $basePath = '';
      if (!empty($this->basePath)) {
        $basePath = $this->basePath . '/';
      }
      $path = realpath($basePath . $path);
    }
    return $path;
  }

  /**
   * Scan views for placeholders.
   *
   * @param string $view An included and PHP processed view.
   *
   * @return string The modified view, stripped from the special content.
   */
  private function preProcessComponent($input) {
    $dom = HtmlDomParser::fromString($input);
    $output = $input;
    if (is_object($dom)) {
      foreach ($this->placeholders as $placeholder => $content) {
        $nodes = $dom->find($placeholder);
        foreach ($nodes as $node) {
          $this->placeholders[$placeholder][] = $node->innerText();
          $node->outerText = '';
        }
      }
      $output = $dom->save();
    }
    return $output;
  }

  public function registerPlaceholder($placeholder) {
    if (!isset($this->placeholders[$placeholder])) {
      $this->placeholders[$placeholder] = [];
    }
    return $this;
  }

  /**
   * Renders a component.
   * If $component is a key predefined via Template::define() the registered path
   * is used. If $component is a readable file, it is used.
   *
   * @param string $component A previouly defined
   * @param mixed $data Additional data passed to the renderer at render-time
   */
  public function render($component, $data = null) {
    $component = trim($component);
    $componentFileName = $component;
    $componentContent = null;
    if (isset($this->components[$component])) {
      $componentFileName = $this->components[$component];
    } elseif (isset($this->renderedComponents[$component])) {
      $componentContent = $this->renderedComponents[$component];
    }
    if ($componentContent === null) {
      $componentFileName = $this->getPreparedPath($componentFileName);
      if (!is_readable($componentFileName)) {
        throw new \Exception("Unable to render `{$component}` (`{$componentFileName}`).");
      }
      // Export data to the output
      foreach ($this->data as $__key => $__value) {
        $$__key = $__value;
      }

      if (($data != null) && is_array($data)) {
        foreach ($data as $__key => $__value) {
          $$__key = $__value;
        }
      }
    }

    try {
      ob_start();
      if ($componentContent === null) {
        include $componentFileName;
      } else {
        echo $componentContent;
      }
      $html = ob_get_contents();
      $html = $this->preProcessComponent($html);
      ob_end_clean();
    } catch (\Exception $e) {
      echo $e;
      die();
    }
    echo $html;
  }

  /**
   * Sets data that will be available in the views.
   * @param array $data An array of key=>value pairs.
   */
  public function setData(array $data) {
    foreach ($data as $key => $value) {
      $this->data[$key] = $value;
    }
    return $this;
  }

  public function getView() {
    $buffer = 'empty-view';
    if (is_readable($this->template)) {
      ob_start();
      include $this->template;
      $buffer = ob_get_contents();
      ob_end_clean();
      $dirty = false;
      $dom = HtmlDomParser::fromString($buffer);
      // Inject custom placeholders content into the final page.
      foreach ($this->placeholders as $placeholder => $content) {
        $i = 0;
        while ($domNode = $dom->find($placeholder, $i++)) {
          $outerText = $domNode->outerText;
          if (count($content)) {
            $outerText = implode("\n", $content);
          }
          $domNode->outerText = $outerText;
          $dirty = true;
        }
      }
      // Save changes to the DOM
      if ($dom && $dirty) {
        $buffer = $dom->save();
      }
    }
    return $buffer;
  }

  /**
   * Get a response, prepares and injects the body (html or other) and returns the modified response object
   * ready to be rendered.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   * @param mixed $content Optional custom content
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function write(\Psr\Http\Message\ResponseInterface $response, $content = null) {
    $body = $response->getBody();
    if ($content === null) {
      $content = $this->getView();
    }
    $body->write($content);
    $response = $response->withBody($body);
    return $response;
  }
} // class

// EOF
