<?php

/**
 * Class that handles all template information from CBIS.
 *
 * @package cbisimport
 */
class CbisTemplate {
  const GENERAL = 91;
  private static $templates = array();
  private $name;
  private $attributes;
  private $parents;

  /**
   * Private constructor, use the static function
   * CbisTemplate::getTemplate() instead.
   *
   * @param int $id 
   */
  private function __construct($id) {
    $this->attributes = array();
    $this->loadDefinition($id);
  }

  /**
   * Gets the template with the given id.
   *
   * @param int $id
   * @return CbisTemplate
   */
  public static function getTemplate($id) {
    if (!isset(self::$templates[$id])) {
      self::$templates[$id] = new CbisTemplate($id);
    }
    return self::$templates[$id];
  }

  /**
   * Recursively loads the template and it's parent templates.
   *
   * @param int $id 
   * @return void
   */
  private function loadDefinition($id) {
    if ($id != CbisTemplate::GENERAL) {
      $template = cbisimport_get_template($id);
      if ($template) {
        $this->name = $template->Name;
        foreach (cbisimport_array($template->Attributes->TemplateAttribute) as $attribute) {
          $this->attributes[$attribute->AttributeId] = $attribute;
        }
      }
      $this->template = $template;
    }
    else {
      $this->name = t('General');
      $templates = cbisimport_get_templates();
      foreach ($templates as $template) {
        foreach (cbisimport_array($template->Attributes->TemplateAttribute) as $attribute) {
          $this->attributes[$attribute->AttributeId] = $attribute;
        }
      }
      $this->template = (object)array(
        'Name' => $this->name,
      );
    }
  }

  /**
   * Checks if the template is an instance of a specific template. That is if
   * the template is the template in question, or if it inherits from it.
   *
   * @param int $template_id
   * @return bool
   */
  public function isInstanceOf($template_id) {
    $match = $this->template->Id == $template_id;
    $match = $match || $this->template->ParentId == $template_id;
    if (!$match && $this->template->ParentId) {
      $parent = self::getTemplate($this->template->ParentId);
      $match = $parent->isInstanceOf($template_id);
    }
    return $match;
  }

  /**
   * Convenience function that loads the required template and sanitizes the
   * product.
   *
   * @param object|array $product
   *  The raw product data or an array of products.
   * @return array
   *  The sanitized product or products.
   */
  public static function sanitize($product) {
    $result = NULL;

    if (is_array($product)) {
      $result = array();
      foreach ($product as $p) {
        $tpl = self::getTemplate(CbisTemplate::GENERAL);
        $result[$product->Id] = $tpl->sanitizeProduct($p);
      }
    }
    else {
      $tpl = self::getTemplate(CbisTemplate::GENERAL);
      $result = $tpl->sanitizeProduct($product);
    }
    return $result;
  }

  /**
   * Names and normalizes the product attributes and other data.
   *
   * @param object $product
   * @return array
   *  The sanitized product.
   */
  public function sanitizeProduct($product) {
    $sane = array();
    foreach ((array)$product as $name => $value) {
      switch ($name) {
        case 'Attributes':
          $value = $this->sanitizeProductAttributes($value->AttributeData);
          break;
        case 'Occasions':
          $occasions = cbisimport_array($value->OccasionObject);
          $value = array();
          foreach ($occasions as $occasion) {
            $o = strtotime('0001-01-01T00:00:00');
            $occasion->StartDate = strtotime($occasion->StartDate);
            $occasion->EndDate = strtotime($occasion->EndDate);
            $occasion->StartTime = $this->getTimeOffset($occasion->StartTime);
            $occasion->EndTime = $this->getTimeOffset($occasion->EndTime);
            $occasion->EntryTime = strtotime($occasion->EntryTime);
            if ($occasion->EntryTime == $o) {
              $occasion->EntryTime = NULL;
            }
            $value[$occasion->Id] = $occasion;
          }
          break;
        case 'PublishedDate':
        case 'RevisionDate':
        case 'ExpirationDate':
          if (!empty($value)) {
            $value = strtotime($value);
          }
          break;
      }
      $sane[$name] = $value;
    }

    $sane['TemplateName'] = $this->name;
    $sane['TemplateParents'] = $this->parents;
    
    return $sane;
  }

  private function getTimeOffset($isotime) {
    $t = date('Y-m-d') . 'T' . substr($isotime, 11, 8) . date('P');
    $o = strtotime($t) - strtotime(date('Y-m-d\T00:00:00P'));
    return $o;
  }

  /**
   * Maps in the attribute values to their system names and flattens their
   * value structure where appropriate.
   *
   * @param array $attributeData 
   * @return array
   *  The sanitized attributes.
   */
  private function sanitizeProductAttributes($attributeData) {
    $attributes = array();
    foreach ($attributeData as $attribute) {
      $def = $this->attributes[$attribute->AttributeId];
      if ($def) {
        switch ($attribute->MetaType) {
          case 'Media':
            $attributes[$def->AttributeSystemName] = cbisimport_array($attribute->Value->MediaList->MediaObject);
            break;
          case 'LocationDistances':
            $attributes[$def->AttributeSystemName] = cbisimport_array($attribute->Value->LocationDistance);
            break;
          case 'String':
            $text = $attribute->Value->Data;
            $text = preg_replace('/<br\/?>/', "\n", $text);
            $text = preg_replace('/\n{3,}/', "\n\n", $text);
            $text = strip_tags($text, '<a>');
            $text = html_entity_decode($text);
            $text = trim($text);
            $attributes[$def->AttributeSystemName] = $text; 
            break;
          default:
            if (isset($attribute->Value->Data)) {
              $attributes[$def->AttributeSystemName] = $attribute->Value->Data;
            }
            else {
              $attributes[$def->AttributeSystemName] = $attribute->Value; 
            }
            break;
        }
      }
      else {
        $attributes['_unknown'][] = $attribute;
      }
    }
    return $attributes;
  }
}
