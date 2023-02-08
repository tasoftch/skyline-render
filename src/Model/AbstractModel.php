<?php
/**
 * Copyright (c) 2018 TASoft Applications, Th. Abplanalp <info@tasoft.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Skyline\Render\Model;


use ArrayAccess;
use Countable;
use Iterator;

abstract class AbstractModel implements ModelInterface, ArrayAccess, Iterator, Countable
{
    protected $data;
    public function __debugInfo() {
        return $this->data;
    }

    protected function resetAll() {
        $this->data = [];
    }

    public function getValueForKey(string $key) {
        return $this->data[$key] ?? NULL;
    }

    public function __isset($name) {
        return isset($this->data[$name]);
    }

    public function &__get($name)
    {
		$a = NULL;
		if(isset($this->data[$name])) {
			$a = &$this->data[$name];
		}
		return $a;
    }

    public function __set($name, $value) {
        if(is_null($name))
            $this->data[] = $value;
        else
           $this->data[$name] = $value;
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }


    public function count(): int
    {
        return count($this->data);
    }

    public function current(): mixed
    {
        return current($this->data);
    }

    public function key(): string|int|null
	{
        return key($this->data);
    }

    public function next(): void
    {
        next($this->data);
    }

    public function rewind(): void
    {
        reset($this->data);
    }
    public function valid(): bool
    {
        return ($this->key() !== null);
    }

    public function offsetExists($offset): bool
    {
        return $this->__isset($offset);
    }
    public function offsetGet($offset): mixed
    {
        return $this->__get($offset);
    }
    public function offsetSet($offset, $value): void
    {
        $this->__set($offset, $value);
    }
    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }
}