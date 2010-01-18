<?php

/**
 * Class that handles all template information from CBIS.
 *
 * @package cbisimport
 */
class CbisTemplate {
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
    $template = cbisimport_get_template($id);
    if ($template) {
      if (!empty($template->ParentId)) {
        $this->parents[] = array('id' => $template->ParentId, 'name' => $this->name);
      }
      $this->name = $template->Name;
      foreach (cbisimport_array($template->Attributes->TemplateAttribute) as $attribute) {
        $this->attributes[$attribute->AttributeId] = $attribute;
      }
    }
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
        $tpl = self::getTemplate($p->TemplateId);
        $result[$product->Id] = $tpl->sanitizeProduct($p);
      }
    }
    else {
      $tpl = self::getTemplate($product->TemplateId);
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
          $value = cbisimport_array($value->OccasionObject);
          foreach ($value as $occasion) {
            $o = strtotime('0001-01-01T00:00:00');
            $occasion->StartDate = strtotime($occasion->StartDate);
            $occasion->EndDate = strtotime($occasion->EndDate);
            $occasion->StartTime = strtotime($occasion->StartTime) - $o;
            $occasion->EndTime = strtotime($occasion->EndTime) - $o;
            $occasion->EntryTime = strtotime($occasion->EntryTime);
            if ($occasion->EntryTime == $o) {
              $occasion->EntryTime = NULL;
            }
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
    }
    return $attributes;
  }
}
