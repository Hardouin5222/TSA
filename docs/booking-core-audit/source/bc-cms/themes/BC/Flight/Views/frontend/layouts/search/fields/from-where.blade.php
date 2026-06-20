@include('Flight::frontend.layouts.search.fields.airport', [
    'inputName' => 'from_where',
    'title' => $field['title'] ?? __('From where'),
    'placeholder' => __('City or airport'),
])
