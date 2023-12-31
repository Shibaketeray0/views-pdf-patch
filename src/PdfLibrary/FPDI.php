<?php
declare(strict_types=1);

namespace Drupal\views_pdf\PdfLibrary;

use Drupal\file\Entity\File;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views_pdf\Entity\ViewsPdfTemplate;

class FPDI extends \setasign\Fpdi\Tcpdf\Fpdi {

  protected static $fontList = NULL;
  protected static $fontListClean = NULL;
  protected static $templateList = NULL;
  protected static $hyphenatePatterns = NULL;
  protected $defaultFontStyle = '';
  protected $defaultFontFamily = 'helvetica';
  protected $defaultFontSize = '11';
  protected $defaultTextAlign = 'L';
  protected $defaultFontColor = '000000';
  protected $defaultPageTemplateFiles = array();
  protected $mainContentPageNumber = 0;
  protected $rowContentPageNumber = 0;
  protected $defaultOrientation = 'P';
  protected $defaultFormat = 'A4';
  protected $addNewPageBeforeNextContent = FALSE;
  protected $elements = [];
  protected $headerFooterData = [];
  protected $headerFooterOptions = [];
  /** @var ViewExecutable */
  protected $view;
  /** @var DisplayPluginBase */
  protected $display;
  protected $y_header = 0;
  protected $y_footer = 0;
  protected $newPage = FALSE;
  protected $lastWritingPage = 0;
  protected $lastWritingYPositions;
  protected $lastWritingPositions;
  protected $lastWritingElement;
  protected $position = '';

  protected $tableHeader = [];

  protected static $defaultFontList = [
    'almohanad' => 'AlMohanad',
    'arialunicid0' => 'ArialUnicodeMS',
    'courier' => 'Courier',
    'courierb' => 'Courier Bold',
    'courierbi' => 'Courier Bold Italic',
    'courieri' => 'Courier Italic',
    'dejavusans' => 'DejaVuSans',
    'dejavusansb' => 'DejaVuSans-Bold',
    'dejavusansbi' => 'DejaVuSans-BoldOblique',
    'dejavusansi' => 'DejaVuSans-Oblique',
    'dejavusanscondensed' => 'DejaVuSansCondensed',
    'dejavusanscondensedb' => 'DejaVuSansCondensed-Bold',
    'dejavusanscondensedbi' => 'DejaVuSansCondensed-BoldOblique',
    'dejavusanscondensedi' => 'DejaVuSansCondensed-Oblique',
    'dejavusansmono' => 'DejaVuSansMono',
    'dejavusansmonob' => 'DejaVuSansMono-Bold',
    'dejavusansmonobi' => 'DejaVuSansMono-BoldOblique',
    'dejavusansmonoi' => 'DejaVuSansMono-Oblique',
    'dejavuserif' => 'DejaVuSerif',
    'dejavuserifb' => 'DejaVuSerif-Bold',
    'dejavuserifbi' => 'DejaVuSerif-BoldItalic',
    'dejavuserifi' => 'DejaVuSerif-Italic',
    'dejavuserifcondensed' => 'DejaVuSerifCondensed',
    'dejavuserifcondensedb' => 'DejaVuSerifCondensed-Bold',
    'dejavuserifcondensedbi' => 'DejaVuSerifCondensed-BoldItalic',
    'dejavuserifcondensedi' => 'DejaVuSerifCondensed-Italic',
    'freemono' => 'FreeMono',
    'freemonob' => 'FreeMonoBold',
    'freemonobi' => 'FreeMonoBoldOblique',
    'freemonoi' => 'FreeMonoOblique',
    'freesans' => 'FreeSans',
    'freesansb' => 'FreeSansBold',
    'freesansbi' => 'FreeSansBoldOblique',
    'freesansi' => 'FreeSansOblique',
    'freeserif' => 'FreeSerif',
    'freeserifb' => 'FreeSerifBold',
    'freeserifbi' => 'FreeSerifBoldItalic',
    'freeserifi' => 'FreeSerifItalic',
    'hysmyeongjostdmedium' => 'HYSMyeongJoStd-Medium-Acro',
    'helvetica' => 'Helvetica',
    'helveticab' => 'Helvetica Bold',
    'helveticabi' => 'Helvetica Bold Italic',
    'helveticai' => 'Helvetica Italic',
    'kozgopromedium' => 'KozGoPro-Medium-Acro',
    'kozminproregular' => 'KozMinPro-Regular-Acro',
    'msungstdlight' => 'MSungStd-Light-Acro',
    'stsongstdlight' => 'STSongStd-Light-Acro',
    'symbol' => 'Symbol',
    'times' => 'Times New Roman',
    'timesb' => 'Times New Roman Bold',
    'timesbi' => 'Times New Roman Bold Italic',
    'timesi' => 'Times New Roman Italic',
    'zapfdingbats' => 'Zapf Dingbats',
    'zarbold' => 'ZarBold'
  ];

  /**
   * This method overrides the parent constructor method.
   * this is need to reset the default values.
   *
   * @param string $orientation
   * @param string $unit
   * @param string $format
   * @param bool $unicode
   * @param string $encoding
   * @param bool $diskcache
   */
  public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=TRUE, $encoding='UTF-8', $diskcache=FALSE) {
    parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache);
    $this->defaultOrientation = $orientation;
    $this->defaultFormat = $format;
  }

  public function setDefaultFontSize($size) {
    $this->defaultFontSize = $size;
  }

  public function setDefaultFontFamily($family) {
    $this->defaultFontFamily = $family;
  }

  public function setDefaultFontStyle($style) {
    $this->defaultFontStyle = $style;
  }

  public function setDefaultTextAlign($style) {
    $this->defaultTextAlign = $style;
  }

  public function setDefaultFontColor($color) {
    $this->defaultFontColor = $color;
  }

  public function setDefaultPageTemplate($path, $key, $pageNumbering = 'main') {
    $this->defaultPageTemplateFiles[$key] = array(
      'path' => $path,
      'numbering' => $pageNumbering
    );
  }

  // Sets up the header & footer.
  // Rendering is deferred to the Header() and Footer() functions.
  public function setViewsHeaderFooter(DisplayPluginBase $display) {
    $this->display = $display;

    $this->y_header = $display->getOption('header_margin');
    $this->SetY(-($this->bMargin - $display->getOption('footer_spacing')));
    $this->y_footer = $this->y;
  }
  /**
   * Function override to output the header.
   */
  function Header() {
    $this->_format_header_footer('header');

    // Output the table header if required.
    if (!empty($this->tableHeader)) {
      $this->cell_padding = $this->cellPaddings;

      foreach ($this->tableHeader as $header) {
        list($x, $y, $label, $headerOptions, $view, $id) = $header;
        $this->renderItem($x, $y, $label, NULL, $headerOptions, $view, $id, FALSE, TRUE);
      }
    }
  }
  /**
   * Function override to output the footer.
   */
  function Footer() {
    $this->_format_header_footer('footer');
  }
  /*
   * Common function to output header or footer.
   *
   * @param $h_f 'header' or 'footer'.
   */
  function _format_header_footer($h_f) {
    $display = $this->display;
    // Get the leading/trailing header/footer option (e.g. "succeed_header").
    if ((empty($this->position) || $this->position == 'closing' || $display->getOption("{$this->position}_{$h_f}")) &&
      !empty($rendered = $display->renderArea("$h_f"))) {
      $this->SetTextColorArray($display->getOption("{$h_f}_font_color"));
      $this->SetFont(
        $display->getOption("{$h_f}_font_family"),
        implode('', $display->getOption("{$h_f}_font_style")),
        $display->getOption("{$h_f}_font_size")
      );
      $yvar = "y_$h_f";
      $this->writeHTMLCell(0, 0,
        (float)$this->lMargin, (float)$this->$yvar,
        format_string($rendered, array('!page' => $this->getPage())),
        0, 0,
        FALSE, TRUE,
        $display->getOption("{$h_f}_text_align")
      );
    }
    if ($h_f == 'footer' && $this->position == 'closing') {
      $this->position = 'succeed';
    }
  }

  /**
   * Converts a hex color into an array with RGB colors.
   */
  public function convertHexColorToArray($hex) {
    if (mb_strlen($hex) == 6) {
      $r = mb_substr($hex, 0, 2);
      $g = mb_substr($hex, 2, 2);
      $b = mb_substr($hex, 4, 2);
      return array(hexdec($r), hexdec($g), hexdec($b));

    }
    elseif (mb_strlen($hex) == 3) {
      $r = mb_substr($hex, 0, 1);
      $g = mb_substr($hex, 1, 1);
      $b = mb_substr($hex, 2, 1);
      return array(hexdec($r), hexdec($g), hexdec($b));

    }
    else {
      return array();
    }
  }

  /**
   * Parse color input into an array.
   *
   * @param string $color
   *   Color entered by the user
   *
   * @return array
   *   Color as an array
   */
  public function parseColor(string $color): array {
    $color = trim($color, ', ');
    $components = explode(',', $color);
    if (count($components) === 1) {
      return $this->convertHexColorToArray($color);
    }
    else {
      // Remove white spaces from comonents:
      foreach ($components as $id => $component) {
        $components[$id] = trim($component);
      }
      return $components;
    }
  }

  /**
   * This method draws a field on the PDF.
   *
   * @param $row
   * @param $options
   * @param \Drupal\views\ViewExecutable $view
   * @param string|null $key
   * @param bool $printLabels
   */
  public function drawContent(ResultRow $row, array $options, ViewExecutable $view, ?string $key = NULL, $printLabels = TRUE): void {

    $content = $view->field[$key]->theme($row);

    if (!is_array($options)) {
      $options = [];
    }

    // Set defaults:
    $options += [
      'position' => [],
      'text' => [],
      'render' => [],
    ];

    $options['position'] += [
      'corner' => 'top_left',
      'x' => 0,
      'y' => 0,
      'object' => 'last_position',
      'width' => 0,
      'height' => 0,
    ];

    $options['text'] += [
      'font_family' => 'default',
      'font_style' => '',
    ];

    $options['render'] += [
      'eval_before' => '',
      'eval_after' => '',
      'bypass_eval_before' => FALSE,
      'bypass_eval_after' => FALSE,
      'custom_layout'     => FALSE,
      'custom_post'       => FALSE,
    ];

    // Grid-mode flag, true if grid options are provided.
    $isgrid = !empty($options['grid']);

    // Get the page dimensions
    $pageDim = $this->getPageDimensions();

    // Check if there is a minimum space defined. If so, then ensure
    // that we have enough space left on this page. If not force adding
    // a new one.
    if (isset($options['render']['minimal_space'])) {
      $enoughSpace = ($this->y + $this->bMargin + $options['render']['minimal_space']) < $pageDim['hk'];
    }
    else {
      $enoughSpace = TRUE;
    }

    // Check if there is a page, if not add it:
    if (!$enoughSpace || $this->getPage() === 0 || $this->addNewPageBeforeNextContent) {
      $this->addNewPageBeforeNextContent = FALSE;
      $this->addPage();
      $this->newPage = TRUE;
    }

    // Get the page dimensions again, because it can be that a new
    // page was added with new dimensions.
    $pageDim = $this->getPageDimensions();

    // Calculate pseudo-margins, in grid mode these define the limits of the cell.
    $lBound = (float)$this->lMargin;
    $tBound = (float)$this->tMargin;
    // For a grid cell, increase margin by cell offset.
    if ($isgrid) {
      if ($options['grid']['new_cell']) {
        // Temporarily set the left margin to the cell boundary,
        // as this is the position the X co-ordinate automatically resets to.
        $lBound = (float)$this->original_lMargin + $options['grid']['x'];
        $this->SetLeftMargin($lBound);
        $this->SetY($tBound);
      }
      $tBound += $options['grid']['y'];
      $rBound = $lBound + $options['grid']['w'];
      $bBound = $tBound + $options['grid']['h'];
    }
    else {
      $rBound = (float)$pageDim['wk'] - (float)$this->rMargin;
      $bBound = (float)$pageDim['hk'] - (float)$this->bMargin;
    }

    // Determine the last writing y coordinate, if we are on a new
    // page we need to reset it back to the top margin.

    if ($this->newPage || ($this->y + $this->bMargin) > $pageDim['hk']) {
      $this->lastWritingYPositions = $this->tMargin;
    }
    else {
      $this->lastWritingYPositions = $this->y;
    }

    // Determine the x and y coordinates
    if ($this->newPage && $options['position']['object'] === 'last_position') {
      $this->defaultTextAlign;
      $x = (float) $this->defaultTextAlign === 'L' ? $this->lMargin : $this->rMargin;
      $y = (float) $this->tMargin;
    }

    if ($this->newPage === FALSE && $options['position']['object'] === 'last_position') {
      $x = (float) $this->x + (float) $options['position']['x'];
      $y = (float) $this->y + (float) $options['position']['y'];
    }

    if ($options['position']['object'] === 'page') {
      switch ($options['position']['corner']) {
        default:
        case 'top_left':
          $x = (float) $options['position']['x'] + $lBound;
          $y = (float) $options['position']['y'] + $tBound;
          break;

        case 'top_right':
          $x = (float) $options['position']['x'] + $rBound;
          $y = (float) $options['position']['y'] + $tBound;
          break;

        case 'bottom_left':
          $x = (float) $options['position']['x'] + $lBound;
          $y = (float) $options['position']['y'] + $bBound;
          break;

        case 'bottom_right':
          $x = (float) $options['position']['x'] + $rBound;
          $y = (float) $options['position']['y'] + $bBound;
          break;
      }
    }

    if (
      $options['position']['object'] == 'self' ||
      //$options['position']['object'] == 'last' or
      preg_match('/field\_(.*)/', $options['position']['object'], $rs)
    ) {
      if ($options['position']['object'] == 'last') {
        $relative_to_element = $this->lastWritingElement;
      }
      elseif ($options['position']['object'] == 'self') {
        $relative_to_element = $key;
      }
      elseif (isset($rs)) {
        $relative_to_element = $rs[1];
      }

      if (isset($this->elements[$relative_to_element])) {

        switch ($options['position']['corner']) {
          default:
          case 'top_left':
            $x = (float) $options['position']['x'] + (float) $this->elements[$relative_to_element]['x'];
            $y = (float) $options['position']['y'] + (float) $this->elements[$relative_to_element]['y'];
            break;

          case 'top_right':
            $x = (float) $options['position']['x'] + (float) $this->elements[$relative_to_element]['x'] + (float) $this->elements[$relative_to_element]['width'];
            $y = (float) $options['position']['y'] + (float) $this->elements[$relative_to_element]['y'];
            break;

          case 'bottom_left':
            $x = (float) $options['position']['x'] + (float) $this->elements[$relative_to_element]['x'];
            $y = (float) $options['position']['y'] + (float) $this->elements[$relative_to_element]['y'] + (float) $this->elements[$relative_to_element]['height'];
            break;

          case 'bottom_right':
            $x = (float) $options['position']['x'] + (float) $this->elements[$relative_to_element]['x'] + (float) $this->elements[$relative_to_element]['width'];
            $y = (float) $options['position']['y'] + (float) $this->elements[$relative_to_element]['y'] + (float) $this->elements[$relative_to_element]['height'];
            break;
        }

        // Handle if the relative element is on another page. So using the
        // the last writing position instead for y.
        if ($this->getPage() != $this->elements[$relative_to_element]['page'] && $options['position']['object'] != 'self') {
          $this->setPage($this->elements[$relative_to_element]['page']);
        }
        elseif ($this->getPage() != $this->elements[$relative_to_element]['page'] && $options['position']['object'] == 'self') {
          $y -= $this->elements[$relative_to_element]['y'] + $this->lastWritingYPositions;
          $this->SetPage($this->lastWritingPage);
        }

      }
      else {
        $x = (float) $this->x;
        $y = (float) $this->lastWritingYPositions;
      }
    }

    // In grid mode, set width and height not to exceed edge of grid cell.
    if ($isgrid) {
      // If start point is outside the cell, just return.
      if ($x >= $rBound || $y >= $bBound) {
        return;
      }
      $maxw = $rBound - $x;
      $maxh = $bBound - $y;
      $options['position']['width'] = ($options['position']['width'] == 0)?
        $maxw : min($options['position']['width'], $maxw);
      $options['position']['height'] = ($options['position']['height'] == 0)?
        $maxh : min($options['position']['height'], $maxh);
    }
    $this->SetX($x);
    $this->SetY($y);
    $this->renderItem($x, $y, $content, $row, $options, $view, $key, $printLabels, FALSE, $isgrid);
  }

  protected function renderItem($x, $y, $content, $row, $options, ViewExecutable $view, $key,
                                $printLabels = TRUE, $istable = FALSE, $isgrid = FALSE): void {

    // Only render if not excluded, and not a header or footer field.
    if (empty($view->field[$key]->options['exclude']) &&
      $options['position']['object'] !== 'header_footer') {

      $pageDim = $this->getPageDimensions();

      // Apply the hyphenation patterns to the content:
      if (!isset($options['text']['hyphenate']) && is_object($view) && is_object($view->display_handler)) {
        $options['text']['hyphenate'] = $view->display_handler->getOption('default_text_hyphenate');
      }

      if (isset($options['text']['hyphenate']) && $options['text']['hyphenate'] != 'none') {
        $patternFile = $options['text']['hyphenate'];
        if ($options['text']['hyphenate'] == 'auto' && is_object($row)) {

          // Workaround:
          // Since "$nodeLanguage = $row->node_language;" does not work anymore,
          // we using this:
          if (isset($row->_field_data['nid']['entity']->language)) {
            $nodeLanguage = $row->_field_data['nid']['entity']->language;

            foreach (self::getAvailableHyphenatePatterns() as $file => $pattern) {
              if (stristr($pattern, $nodeLanguage) !== FALSE) {
                $patternFile = $file;
                break;
              }
            }
          }
        }

        // TODO: Refactor to entity type hyphen.
        $patternFile = 'none';

        if (file_exists($patternFile)) {
          if (method_exists('TCPDF_STATIC', 'getHyphenPatternsFromTEX')) {
            $hyphen_patterns = TCPDF_STATIC::getHyphenPatternsFromTEX($patternFile);
          }
          else {
            $hyphen_patterns = $this->getHyphenPatternsFromTEX($patternFile);
          }

          // Bugfix if you like to print some html code to the PDF, we
          // need to prevent the replacement of this tags.
          $content = str_replace('&gt;', '&amp;gt;', $content);
          $content = str_replace('&lt;', '&amp;lt;', $content);
          $content = $this->hyphenateText($content, $hyphen_patterns);

        }
      }

      // Set css variable
      if (is_object($view) && is_object($view->display_handler)) {
        $css_file = $view->display_handler->getOption('css_file');
      }

      $font_size = empty($options['text']['font_size']) ? $this->defaultFontSize : $options['text']['font_size'] ;
      $font_family = ($options['text']['font_family'] == 'default' || empty($options['text']['font_family'])) ? $this->defaultFontFamily : $options['text']['font_family'];
      $font_style = is_array($options['text']['font_style']) ? $options['text']['font_style'] : $this->defaultFontStyle;
      $textColor = !empty($options['text']['color']) ? $this->parseColor($options['text']['color']) : $this->parseColor($this->defaultFontColor);

      $w = $options['position']['width'];
      $h = $options['position']['height'];
      $border = !empty($options['text']['border'])? $options['text']['border'] : 0;
      $align = isset($options['text']['align']) ? $options['text']['align'] : $this->defaultTextAlign;
      $fill = 0;
      $ln = 1;
      $reseth = TRUE;
      $stretch = 0;
      $ishtml = ($istable || $isgrid)? 0 : (isset($options['render']['is_html']) ? $options['render']['is_html'] : 1);
      $stripHTML = !$ishtml;
      $autopadding = TRUE;
      if ($istable) {
        // For table mode we use a precise height.
        $maxh = $h;
      }
      elseif ($isgrid) {
        // For grid mode we use auto-height, with max height defining the grid boundary.
        $maxh = $h;
        $h = 0;
      }
      else {
        $maxh = 0;
      }

      // Render Labels
      $prefix = '';
      if ($printLabels && !empty($view->field[$key]->options['label'])) {
        $prefix = $view->field[$key]->options['label'];
        if ($view->field[$key]->options['element_label_colon']) {
          $prefix .= ':';
        }
        $prefix .= ' ';
      }
      if (!empty($prefix) && !$stripHTML) {
        // If label HTML has been customised, add tag and classes as required.
        $label_info = $view->field[$key]->options;
        if ($tag = $label_info['element_label_type']) {
          $classes = array();
          if ($label_info['element_label_class']) {
            $classes[] = $label_info['element_label_class'];
          }
          if ($label_info['element_default_classes']) {
            $classes[] = 'views-label';
            $classes[] = "views-label-{$label_info['id']}";
          }
          $class = !empty($classes)? ' class="' . implode(' ', $classes) . '"' : '';
          $prefix = "<$tag$class>$prefix</$tag>";
        }
      }

      // Run eval before.
      if (defined('VIEWS_PDF_PHP') && !empty($options['render']['eval_before'])) {
        if (empty($options['render']['bypass_eval_before'])) {
          $content = php_eval($options['render']['eval_before']);
        }
        else {
          eval($options['render']['eval_before']);
        }
      }
      if (!empty($options['render']['custom_layout'])) {
        // Custom layout hook.
        $layout_data = array (
          'x'          => &$x,
          'y'          => &$y,
          'h'          => &$h,
          'w'          => &$w,
          'content'    => &$content,
          'key'        => &$key,
          'view'       => &$view,
          'this'       => &$this,
          'border'     => &$border,
          'color'      => &$textColor,
          'font'       => &$font_family,
          'font_style' => &$font_style,
          'font_size'  => &$font_size,

        );
        // TODO: Refactor.
        // drupal_alter('views_pdf_custom_layout', $layout_data);
      }

      // Add css if there is a css file set and stripHTML is not active.
      if (!empty($css_file) && is_string($css_file) && !$stripHTML && !empty($content)) {
        $content = '<link type="text/css" rel="stylesheet" media="all" href="' . $css_file . '" />' . PHP_EOL . $content;
      }

      // Set Text Color.
      $this->SetTextColorArray($textColor);

      $pluginViewsPDFFont = \Drupal::service('plugin.manager.views_pdf.font')->getDefinitions();
      if (in_array($font_family, $pluginViewsPDFFont, TRUE)) {
        $providerPath = \Drupal::service('extension.list.module')->getPath($pluginViewsPDFFont[$font_family]['provider']);
        $fontfile = "$providerPath/$pluginViewsPDFFont[$font_family]['base_dir']/$pluginViewsPDFFont[$font_family]['font_file']";

        $fontData = $this->AddFont($font_family, implode('', $font_style), $fontfile);
        $this->SetFont($font_family, $fontData['style'], $font_size);
      }
      else {
        $this->SetFont($font_family, implode('', $font_style), $font_size);
      }

      // Save the last page before starting writing, this
      // is needed to detect if we write over a page. Then we need
      // to reset the y coordinate for the 'last_writing' position option.
      $this->lastWritingPage = $this->getPage();

      if ($stripHTML) {
        $content = html_entity_decode(strip_tags((string) $content), ENT_QUOTES | ENT_HTML401);
      }

      // Write the content of a field to the pdf file:
      if ($istable) {
        $this->cell_padding['T'] = $this->cell_padding['B'] = !empty($options['text']['vpad'])?
          $options['text']['vpad'] : 0;
      }
      $this->MultiCell((float) $w, (float) $h, $prefix . $content, $border, $align, $fill, $ln, $x, $y, $reseth, $stretch, $ishtml, $autopadding, (float) $maxh);

      // Post render.
      if (!empty($options['render']['custom_post'])) {
        // TODO: Refactor.
        // drupal_alter('views_pdf_custom_post', $view);
      }
      // Run eval after.
      if (defined('VIEWS_PDF_PHP') && !empty($options['render']['eval_after'])) {
        // Questionable whether there's any point supporting php_eval()
        // after render. It has no access to the local context, and can
        // only return a value. But the output has already been written
        // so there's nothing that can be done with the return value!
        if (empty($options['render']['bypass_eval_after'])) {
          php_eval($options['render']['eval_after']);
        }
        else {
          eval($options['render']['eval_after']);
        }
      }

      // Write Coordinates of element.
      $this->elements[$key] = array(
        'x' => $x,
        'y' => $y,
        'width' => empty($w) ? ($pageDim['wk'] - $this->rMargin-$x) : $w,
        'height' => $this->y - $y,
        'page' => $this->lastWritingPage,
      );

      $this->lastWritingElement = $key;
    }
    $this->newPage = FALSE;
  }

  /**
   * This method draws a table on the PDF.
   *
   * @param \Drupal\views\ViewExecutable $view
   * @param $options
   */
  public function drawTable(ViewExecutable $view, $options) {

    $rows = $view->result;
    $columns = $view->field;
    $pageDim = $this->getPageDimensions();

    $width = (float)$pageDim['wk'] - (float)$this->lMargin - (float)$this->rMargin;

    $sumWidth = 0;
    $numberOfColumnsWithoutWidth = 0;

    // Default header height is height of default font.
    $options['position']['header_style']['height'] = $this->getCellHeight($this->defaultFontSize / $this->k);

    // Compute the row height as the max of default font size and explicit row height.
    $options['position']['body_style']['height'] = max(
      $options['position']['row_height'], $options['position']['header_style']['height']
    );

    // Create a safety height padding, in current units.
    // Without this, FP rounding errors can result in the computed row height
    // being fractionally too small on occasional rows, making them invisible.
    $safety = 0.01 / $this->k;
    // Pre-process column info to determine layout parameters.
    $borderpad = array('header_style' => $safety, 'body_style' => $safety);
    foreach ($columns as $id => $column) {

      // Check for hide-empty columns and scan results to check if they are empty.
      if (!empty($options['info'][$id]['empty']['hide_empty']) && ($row = reset($rows))) {
        do {
          $content = $view->field[$id]->theme($row);
        } while (empty($content) && ($row = next($rows)));
        // If the loop got to the end of the rows, then they are all empty.
        if (!$row) {
          $column->options['exclude'] = TRUE;
        }
      }
      // Skip excluded fields and the page-break field.
      if (empty($column->options['exclude']) && $id != 'page_break') {

        // Options are merge of specific options and defaults.
        if (isset($options['info']['_default_'])) {
          if (!empty($options['info'][$id])) {
            // Merge column specifics with column defaults.
            $options['info'][$id] = array_replace_recursive($options['info']['_default_'], $options['info'][$id]);
          }
          else {
            $options['info'][$id] = $options['info']['_default_'];
          }
        }
        elseif (empty($options['info'][$id])) {
          $options['info'][$id] = array();
        }

        if (!empty($options['info'][$id]['position']['width'])) {
          $sumWidth += $options['info'][$id]['position']['width'];
        }
        else {
          $numberOfColumnsWithoutWidth++;
        }

        foreach(array('header_style', 'body_style') as $style) {

          $font_size = empty($options['info'][$id][$style]['text']['font_size'])?
            $this->defaultFontSize : $options['info'][$id][$style]['text']['font_size'];

          $cell_height = $this->getCellHeight($font_size / $this->k);

          // Add extra padding if specified.
          if (!empty($options['info'][$id][$style]['text']['vpad'])) {
            $cell_height += $options['info'][$id][$style]['text']['vpad'] * 2;
          }
          // Increase row height if column font size requires.
          $options['position'][$style]['height'] = max($options['position'][$style]['height'], $cell_height);

          // Get extra vertical padding required if borders are present.
          if (!empty($options['info'][$id][$style]['text']['border'])) {
            $this->cell_padding['T'] = $this->cell_padding['B'] = 0;
            $extra_paddings = $this->adjustCellPadding($options['info'][$id][$style]['text']['border']);
            $borderpad[$style] = max($borderpad[$style], $extra_paddings['T'] + $extra_paddings['B']);
          }
        }
      }
    }
    $defaultColumnWidth = $numberOfColumnsWithoutWidth > 0?
      $defaultColumnWidth = ($width - $sumWidth) / $numberOfColumnsWithoutWidth : 0;

    // Increase heights to allow for borders.
    foreach(array('header_style', 'body_style') as $style) {
      $options['position'][$style]['height'] += $borderpad[$style];
    }

    // Get table header spacing, or set to 0 if not using header.
    // Note the value of $hspace is the distance from the top of the header to the first line,
    // but the option value in the settings UI is the space from the bottom of the header
    // to the first line.
    if (empty($options['position']['use_header'])) {
      $hspace = 0;
    }
    else {
      $hspace = $options['position']['header_style']['height'];
      if (is_numeric($options['position']['h'])) {
        $hspace += $options['position']['h'];
      }
    }
    // Increase the top margin by the table header spacing.
    $this->tMargin += $hspace;
    $y = (float)$this->tMargin;
    $x = (float)$this->lMargin;

    // Add default option values, and gather all the column header data into an array for use by the Header() function.
    $xh = $x;
    $yh = $y - $hspace;
    foreach ($columns as $id => $column) {
      // Skip excluded fields and the page-break field.
      if (empty($column->options['exclude']) && $id != 'page_break') {

        foreach(array('header_style', 'body_style') as $style) {
          $options['info'][$id][$style] += array(
            'position' => array(),
            'text' => array(),
            'render' => array(),
          );

          $options['info'][$id][$style]['position'] += array(
            'corner' => 'top_left',
            'x' => NULL,
            'y' => NULL,
            'object' => '',
            'width' => NULL,
            'height' => NULL,
          );

          $options['info'][$id][$style]['text'] += array(
            'font_family' => 'default',
            'font_style' => array(),
          );

          $options['info'][$id][$style]['render'] += array(
            'eval_before' => '',
            'eval_after' => '',
          );
        }

        if ($hspace) {
          $headerOptions = $options['info'][$id]['header_style'];

          $headerOptions['position']['width'] = !empty($options['info'][$id]['position']['width'])?
            $options['info'][$id]['position']['width'] : $defaultColumnWidth;

          $headerOptions['position']['height'] = $options['position']['header_style']['height'];

          // Save the parameters for rendering the column headers for use by the Header() function.
          $this->tableHeader[] = array($xh, $yh, $column->options['label'], $headerOptions, &$view, $id);
          $xh += $headerOptions['position']['width'];
        }
      }
    }
    // Save default paddings for use in the header.
    $this->cellPaddings = $this->cell_padding;

    // Add the first page, this will print the header.
    $this->addPage();

    $rowX = $x;
    $view->row_index = 0;

    $break = FALSE;
    foreach ($rows as $row) {
      $x = $rowX;

      // If last row forced a new page, or this row would overflow page, add a page.
      if ($break ||
        ($this->y + $this->bMargin + $options['position']['body_style']['height']) > $pageDim['hk']) {
        $break = FALSE;
        $this->addPage();
      }

      $y = (float)$this->y;
      foreach ($columns as $id => $column) {
        // Always render the field in order to generate tokens for other fields.
        $content = $view->field[$id]->theme($row);

        if (empty($column->options['exclude'])) {

          if ($id == 'page_break') {
            $break = !empty($content);
          }
          else {
            $bodyOptions = $options['info'][$id]['body_style'];

            $bodyOptions['position']['width'] = !empty($options['info'][$id]['position']['width'])?
              $options['info'][$id]['position']['width'] : $defaultColumnWidth;

            $bodyOptions['position']['height'] = $options['position']['body_style']['height'];

            $this->renderItem($x, $y, $content, $row, $bodyOptions, $view, $id, FALSE, TRUE);

            // Set x to start position of next cell
            $x += (float)$bodyOptions['position']['width'];
          }
        }
      }
      // Set y position to start of next row.
      $this->SetY($y + (float)$options['position']['body_style']['height']);

      $view->row_index++;
    }
    // Empty the table header data so as not to print on a trailing template.
    $this->tableHeader = array();
  }

  /**
   * This method adds a existing PDF document to the current document. If
   * the file does not exists this method will return 0. In all other cases
   * it will returns the number of the added pages.
   *
   * @param $path string Path to the file
   * @param $position 'leading' or 'succeed' according to position of document
   * @return integer Number of added pages
   */
  public function addPdfDocument($path = '', $position = '') : int {
    $this->position = $position;
    $format = [];

    if (empty($path) || !file_exists($path)) {
      return 0;
    }

    $numberOfPages = $this->setSourceFile($path);
    for ($i = 1; $i <= $numberOfPages; $i++) {

      $page = $this->importPage($i);

      // TODO: Extract to a method.
      $dim = $this->getTemplateSize($page);
      $format[0] = $dim['width'];
      $format[1] = $dim['height'];

      if ($dim['width'] > $dim['height']) {
        $orientation = 'L';
      }
      else {
        $orientation = 'P';
      }
      $this->setPageFormat($format, $orientation);
      parent::addPage();

      // Ensure that all new content is printed to a new page
      $this->y = 0;

      $this->useTemplate($page, 0, 0);
      $this->addNewPageBeforeNextContent = TRUE;
    }

    return $numberOfPages;

  }

  /**
   * This method resets the page number. This is useful if you want to start
   * the numbering by zero.
   */
  public function resetRowPageNumber() {
    $this->rowContentPageNumber = 0;
  }

  /**
   * This method adds a new page to the PDF.
   */
  public function addPage($orientation = '', $format = '', $keepmargins = false, $tocpage = false, $path = NULL, $reset = FALSE, $numbering = 'main') {

    // Do not add any new page, if we are writing
    // in the footer or header.
    if ($this->InFooter) {
      return;
    }

    $this->mainContentPageNumber++;
    $this->rowContentPageNumber++;

    // Prevent a reset without any template
    if ($reset == TRUE && (empty($path) || !file_exists($path))) {
      parent::addPage();
      $this->setPageFormat($this->defaultFormat, $this->defaultOrientation);
      return;
    }

    $files = $this->defaultPageTemplateFiles;

    // Reset with new template
    if ($reset) {
      $files = array();
    }

    if ($path != NULL) {
      $files[] = array('path' => $path, 'numbering' => $numbering);
    }

    $format = FALSE;
    foreach ($files as $file) {
      if (!empty($file['path']) && file_exists($file['path'])) {
        $path = realpath($file['path']);

        $numberOfPages = $this->setSourceFile($path);
        if ($file['numbering'] == 'row')  {
          $index = min($this->rowContentPageNumber, $numberOfPages);
        }
        else {
          $index = min($this->mainContentPageNumber, $numberOfPages);
        }


        $page = $this->importPage($index);

        // ajust the page format (only for the first template)
        if ($format === FALSE) {

          $dim = $this->getTemplateSize($page);
          $format[0] = $dim['width'];
          $format[1] = $dim['height'];
          //$this->setPageFormat($format);
          if ($dim['width'] > $dim['height']) {
            $orientation = 'L';
          }
          else {
            $orientation = 'P';
          }
          parent::addPage();
          $this->setPageFormat($format, $orientation);
        }

        // Apply the template
        $this->useTemplate($page, 0, 0);
      }
    }

    // if all paths were empty, ensure that at least the page is added
    if ($format == FALSE) {
      parent::addPage();
      $this->setPageFormat($this->defaultFormat, $this->defaultOrientation);
    }

  }

  /**
   * Sets the current header and footer of the page.
   *
   * @param \Drupal\views\ResultRow $record
   * @param array $options
   * @param \Drupal\views\ViewExecutable $view
   */
  public function setHeaderFooter(ResultRow $record, array $options, ViewExecutable $view): void {
    $this->headerFooterData[$this->getPage()] = $record;
    $this->headerFooterOptions = $options;
    $this->view = $view;
  }

  /**
   * Close the document. This is called automatically by
   * TCPDF::Output().
   */
  public function Close() {
    // Print the Header & Footer

    for ($page = 1; $page <= $this->getNumPages(); $page++) {
      $this->setPage($page);

      if (isset($this->headerFooterData[$page])) {
        if (is_array($this->headerFooterOptions['formats'])) {
          $record = $this->headerFooterData[$page];
          foreach ($this->headerFooterOptions['formats'] as $id => $options) {
            if ($options['position']['object'] == 'header_footer') {
              $fieldOptions = $options;
              $fieldOptions['position']['object'] = 'page';
              $this->InFooter = TRUE;

              // backup margins
              $ml = $this->lMargin;
              $mr = $this->rMargin;
              $mt = $this->tMargin;
              $this->SetMargins(0, 0, 0);

              $this->drawContent($record, $fieldOptions, $this->view, $id);
              $this->InFooter = FALSE;

              // restore margins
              $this->SetMargins($ml, $mt, $mr);
            }
          }
        }
      }
    }

    // call parent:
    parent::Close();
  }

  /**
   * This method returns a list of current uploaded files.
   */
  public static function getAvailableTemplates() {
    if (self::$templateList !== NULL) {
      return self::$templateList;
    }

    self::$templateList = ViewsPdfTemplate::loadMultiple();

    return self::$templateList;
  }

  /**
   * This method returns the path to a specific template.
   *
   * @param $template
   *
   * @return string
   */
  public static function getTemplatePath($template) {
    $template = ViewsPdfTemplate::load($template);

    $file = File::load($template->get('template')[0]);

    return \Drupal::service('file_system')->realpath($file->getFileUri());
  }

  protected static function customsFontProviders(array $font_mapping): array {
    $pluginViewsPDFFont = \Drupal::service('plugin.manager.views_pdf.font');

    foreach ($pluginViewsPDFFont->getDefinitions() as $font) {
      $font_mapping[$font['id']] = $font['label'];
    }

    return $font_mapping;
  }

  /**
   * This method returns a list of available fonts.
   */
  public static function getAvailableFonts() {
    if (self::$fontList != NULL) {
      return self::$fontList;
    }

    // Get all pdf files with the font list: K_PATH_FONTS
    $fonts = \Drupal::service('file_system')->scanDirectory( K_PATH_FONTS, '/.php$/', array('nomask' => '/(\.\.?|CVS)$/', 'recurse' => FALSE), 1);
    $cache = \Drupal::service('cache.data')->get('views_pdf_cached_fonts');

    $cached_font_mapping = NULL;

    if (is_object($cache)) {
      $cached_font_mapping = $cache->data;
    }

    if (is_array($cached_font_mapping)) {
      $font_mapping = array_merge(self::$defaultFontList, $cached_font_mapping);
    }
    else {
      $font_mapping = self::$defaultFontList;
    }

    foreach ($fonts as $font) {
      $name = self::getFontNameByFileName($font->uri);
      if (isset($name)) {
        $font_mapping[$font->name] = $name;
      }
    }

    $font_mapping = self::customsFontProviders($font_mapping);

    asort($font_mapping);

    \Drupal::service('cache.data')->set('views_pdf_cached_fonts', $font_mapping);

    // Remove all fonts without name
    foreach ($font_mapping as $key => $font) {
      if (empty($font)) {
        unset($font_mapping[$key]);
      }

    }

    self::$fontList = $font_mapping;

    return $font_mapping;
  }

  /**
   * This method returns a cleaned up version of the font list.
   */
  public static function getAvailableFontsCleanList() {
    if (self::$fontListClean != NULL) {
      return self::$fontListClean;
    }

    $clean = self::getAvailableFonts();

    foreach ($clean as $key => $font) {

      // Unset bold, italic, italic/bold fonts
      unset($clean[ ($key . 'b') ]);
      unset($clean[ ($key . 'bi') ]);
      unset($clean[ ($key . 'i') ]);

    }

    self::$fontListClean = $clean;

    return $clean;
  }

  public static function getPathTcpdf(): string {

    $path = static function (): string {
      $tcpdf = new \TCPDF();
      $object = new \ReflectionObject($tcpdf);
      $method = $object->getMethod('__construct');
      $declaringClass = $method->getDeclaringClass();
      $tcpdf->_destroy(true);
      return (string) $declaringClass->getFilename();
    };

    return $path();
  }

  public static function pdfGetPageFormats(): array {
    return [
      'custom' => t('Custom'),
      '4A0' => '4A0',
      '2A0' => '2A0',
      'A0' => 'A0',
      'A1' => 'A1',
      'A2' => 'A2',
      'A3' => 'A3',
      'A4' => 'A4',
      'A5' => 'A5',
      'A6' => 'A6',
      'A7' => 'A7',
      'A8' => 'A8',
      'A9' => 'A9',
      'A10' => 'A10',
      'B0' => 'B0',
      'B1' => 'B1',
      'B2' => 'B2',
      'B3' => 'B3',
      'B4' => 'B4',
      'B5' => 'B5',
      'B6' => 'B6',
      'B7' => 'B7',
      'B8' => 'B8',
      'B9' => 'B9',
      'B10' => 'B10',
      'C0' => 'C0',
      'C1' => 'C1',
      'C2' => 'C2',
      'C3' => 'C3',
      'C4' => 'C4',
      'C5' => 'C5',
      'C6' => 'C6',
      'C7' => 'C7',
      'C8' => 'C8',
      'C9' => 'C9',
      'C10' => 'C10',
      'RA0' => 'RA0',
      'RA1' => 'RA1',
      'RA2' => 'RA2',
      'RA3' => 'RA3',
      'RA4' => 'RA4',
      'SRA0' => 'SRA0',
      'SRA1' => 'SRA1',
      'SRA2' => 'SRA2',
      'SRA3' => 'SRA3',
      'SRA4' => 'SRA4',
      'LETTER' => 'LETTER',
      'LEGAL' => 'LEGAL',
      'EXECUTIVE' => 'EXECUTIVE',
      'FOLIO' => 'FOLIO',
      'MEMO' => 'MEMO',
    ];
  }

  /**
   * This method returns a list of hyphenation patterns, that are
   * available.
   *
   * @deprecated Have to refactor to Entity Huphenation
   */
  public static function getAvailableHyphenatePatterns() {
    if (self::$hyphenatePatterns != NULL) {
      return self::$hyphenatePatterns;
    }

    return null;

    self::$hyphenatePatterns = array();

    $files = file_scan_directory(views_pdf_get_library('tcpdf') . '/hyphenate_patterns', '/.tex$/', array('nomask' => '/(\.\.?|CVS)$/'), 1);

    foreach ($files as $file) {
      self::$hyphenatePatterns[basename($file->uri)] = str_replace('hyph-', '', $file->name);
    }


    return self::$hyphenatePatterns;
  }

  /**
   * This method returns the name of a given font.
   */
  protected static function getFontNameByFileName($path) {
    include $path;
    if (isset($name)) {
      return $name;
    }
    else {
      return NULL;
    }
  }
}
