<?php
class ParsedownTargetBlank extends Parsedown
{
    protected function inlineLink($Excerpt)
    {
        $element = parent::inlineLink($Excerpt);
        if ($element && isset($element['element']['attributes']['href'])) {
            $element['element']['attributes']['target'] = '_blank';
            $element['element']['attributes']['rel'] = 'noopener noreferrer';
        }
        return $element;
    }
}
