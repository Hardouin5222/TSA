@include('Flight::frontend.layouts.search.fields.airport', [
    'inputName' => 'to_where',
    'title' => $field['title'] ?? __('To where'),
    'placeholder' => __('City or airport'),
])
