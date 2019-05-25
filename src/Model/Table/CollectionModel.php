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

namespace Skyline\Render\Model\Table;
use Skyline\Render\Model\ArrayModel;


class CollectionModel extends ArrayModel implements TableViewDataSourceInterface
{
    private $itemsCountPerRow = 3;



    public function addItem($item) {
        $this->data[] = $item;
    }

    public function removeItem($item) {
        $idx = array_search($item, $this->data);
        if($idx !== false)
            unset($this->data[$idx]);
    }

    public function getItemAtIndex(int $idx) {
        $key = array_keys($this->data)[$idx] ?? NULL;
        return $this->data[$key] ?? NULL;
    }

    public function getNumberOfRows(): int
    {
        $num = count($this->data);
        if($num) {
            $ior = $this->getItemsCountPerRow();
            if($ior < 1)
                $ior = 1;
            return ceil($num / $ior);
        }
        return 0;
    }

    public function getNumberOfCols(int $row): int
    {
        $realRow = $row * $this->getItemsCountPerRow();
        $diff = count($this->data) - $realRow;
        $ct = $this->getItemsCountPerRow();

        return ($diff < 1 || $diff > $ct) ? $ct : $diff ;
    }

    public function getCellDataOfCol(int $row, int $column)
    {
        $realRow = $row * $this->getItemsCountPerRow() + $column;
        return $this->getItemAtIndex($realRow);
    }

    /**
     *
     * @return \Traversable
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function getItemsCountPerRow(): int
    {
        return $this->itemsCountPerRow;
    }

    /**
     * @param int $itemsCountPerRow
     */
    public function setItemsCountPerRow(int $itemsCountPerRow): void
    {
        $this->itemsCountPerRow = $itemsCountPerRow;
    }


    public function getCellDataKeyOfCol(int $row, int $column): ?string
    {
        $realRow = $row * $this->getItemsCountPerRow() + $column;
        return array_keys($this->data)[$realRow] ?? NULL;
    }


}