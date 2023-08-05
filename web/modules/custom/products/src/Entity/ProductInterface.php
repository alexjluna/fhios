<?php

namespace Drupal\products\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Represents a product entity.
 */
interface ProductInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the product name.
   *
   * @return string
   *   The product name.
   */
  public function getName();

  /**
   * Sets the product name.
   *
   * @param string $name
   *   Set de name product.
   *
   * @return \Drupal\products\Entity\ProductInterface
   *   The called Product entity.
   */
  public function setName($name);

  /**
   * Gets the product number.
   *
   * @return int
   *   The number product.
   */
  public function getProductNumber();

  /**
   * Sets the Product number.
   *
   * @param int $number
   *   Set de number product.
   *
   * @return \Drupal\products\Entity\ProductInterface
   *   The called Product entity
   */
  public function setProductNumber($number);

  /**
   * Gets the Product remote ID.
   *
   * @return string
   *   The remote id product.
   */
  public function getRemoteId();

  /**
   * Sets the Product remote ID.
   *
   * @param string $id
   *   Set remote id product.
   *
   * @return \Drupal\products\Entity\ProductInterface
   *   The called Product entity.
   */
  public function setRemoteId($id);

  /**
   * Gets the Product source.
   *
   * @return string
   *   The source product.
   */
  public function getSource();

  /**
   * Sets the Product source.
   *
   * @param string $source
   *   Set the source product.
   *
   * @return \Drupal\products\Entity\ProductInterface
   *   The called Product entity.
   */
  public function setSource($source);

  /**
   * Gets the Product creation timestamp.
   *
   * @return int
   *   The created time product.
   */
  public function getCreatedTime();

  /**
   * Sets the Produtc creation timestamp.
   *
   * @param int $timestamp
   *   Set the created time product.
   *
   * @return \Drupal\products\Entity\ProductInterface
   *   The called Product entity.
   */
  public function setCreatedTime($timestamp);

}
