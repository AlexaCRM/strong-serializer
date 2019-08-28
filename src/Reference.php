<?php
/**
 * Copyright 2019 AlexaCRM
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
 * associated documentation files (the "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE
 * OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace AlexaCRM\StrongSerializer;

use Closure;

/**
 * Describes how a certain piece of data should be transformed type-wise.
 */
class Reference {

    /**
     * Fully-qualified class name to convert the data into.
     *
     * @var string
     */
    protected $className;

    /**
     * Whether the referenced data should be constructed as a collection.
     *
     * @var bool
     */
    protected $isCollection = false;

    /**
     * Whether the referenced data should be constructed as a map.
     *
     * @var bool
     */
    protected $isMap = false;

    /**
     * The class field name to use as the map key.
     *
     * @var string
     */
    protected $mapKeyName;

    /**
     * The map of class field values to be cast.
     *
     * @var Reference[]
     */
    protected $castMap = [];

    /**
     * Class resolver closure.
     *
     * @var Closure
     */
    protected $classResolver;

    /**
     * Reference constructor.
     *
     * @param string|Closure $classOrResolver
     */
    public function __construct( $classOrResolver ) {
        if ( $classOrResolver instanceof Closure ) {
            $this->classResolver = $classOrResolver;
        } else {
            $this->className = $classOrResolver;
        }
    }

    /**
     * Creates a new type collection reference.
     *
     * @return static
     */
    public function toCollection() {
        $ref = clone $this;

        $ref->isCollection = true;

        $ref->isMap = false;
        $ref->mapKeyName = null;

        return $ref;
    }

    /**
     * Creates a new type map with the given class field name as key.
     *
     * @param string $keyName
     *
     * @return static
     */
    public function toMap( string $keyName ) {
        $ref = clone $this;

        $ref->isCollection = false;

        $ref->isMap = true;
        $ref->mapKeyName = $keyName;

        return $ref;
    }

    /**
     * Creates a strongly-typed object as specified during instantiation.
     *
     * This is the default state.
     *
     * @return static
     */
    public function toObject() {
        $ref = clone $this;

        $ref->isCollection = false;
        $ref->isMap = false;
        $ref->mapKeyName = null;

        return $ref;
    }

    /**
     * Casts the specified field value according to reference rules.
     *
     * @param string $fieldName
     * @param Reference $ref
     *
     * @return $this
     */
    public function addFieldCast( string $fieldName, Reference $ref ) {
        $ref = clone $this;

        $ref->castMap[$fieldName] = $ref;

        return $this;
    }

    /**
     * Returns the referenced class name, possibly based on provided data.
     *
     * @param $data
     *
     * @return string
     */
    public function getClassName( $data ) {
        if ( $this->classResolver instanceof Closure ) {
            return $this->classResolver->call( $this, $data );
        }

        return $this->className;
    }

    /**
     * Returns the map of type casts for class fields.
     *
     * @return Reference[]
     */
    public function getCastMap() {
        return $this->castMap;
    }

    /**
     * Tells whether the referenced type should be enclosed into a collection.
     *
     * @return bool
     */
    public function isCollection() {
        return $this->isCollection && !$this->isMap;
    }

    /**
     * Tells whether the referenced type should be enclosed into a map.
     *
     * @return bool
     */
    public function isMap() {
        return $this->isMap && !$this->isCollection;
    }

    /**
     * Returns the class field to be used as the map key.
     *
     * @return string|null
     */
    public function getMapKey() {
        return $this->mapKeyName;
    }

}
