<?php

namespace Keletos\Component\Form;

class Form extends \Keletos\Component\Component {

    public static function createSelect(array $attributes = [], array $options = [], string $selected = null, bool $addEmptyOption = true) : string {

        $default = 'Please select...';

        if (array_key_exists('id', $attributes) && !array_key_exists('name', $attributes)) {
            $attributes['name'] = $attributes['id'];
        }

        $s = '<select ';

        foreach ($attributes as $k => $v) {
            $s .= $k . '="' . $v . '"';
        }

        $s .= '>';

        if ($addEmptyOption) {
            $options = ['' => $default] + $options;
            if (is_null($selected)) {
                $selected = $default;
            }
        }

        foreach ($options as $k => $v) {

            if (is_array($v)) {
                $k = $v['name'];
                $v = $v['value'];
            } elseif ($k !== '') {
                $k = $v;
            }

            $s .= '<option value="' . $k . '" ' . ($selected === $v ? 'selected' : '');

            if ($v === $default) {
                $s .= ' class="hidden"';
            }

            $s .= ">$v</option>";
        }

        $s .= '</select>';

        return $s;

    }
}
