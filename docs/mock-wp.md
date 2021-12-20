# Mock WP_Query

---
To mock the current global $wp_query variable. This allows us to change the current WP behavior during unit test. Identifying where we are and simulate and control various is_[function], eg. is_single(), etc...

    $this->mock->wp( [ ... ] );

To reset and restore the global $wp_query

    $this->mock->wp()->reset();

Mock with a custom query

    $this->mock->wp( [
        'query' => [ ... ],
    ] );

Mock with an instance of WP_Query object

    $this->mock->wp( [
        'wp_query' => new \WP_Query( [ ... ] ),
    ] );

Allow mocked flags/features  

    $this->mock->wp( [
        'is_author'     => true|false,
        'is_single'     => true|false,
        'is_single'     => true|false,
        'is_singular'   => true|false,
        'is_attachment' => true|false,
        'is_front_page' => true|false,
        'is_home'       => true|false,
        'query_vars' => [
            'name' => 'value',
            'name2' => 'value2',
            ...
        ],
        'request'               => <string>,
        'comment_count'         => <number>,
        'max_num_pages'         => <number>,
        'max_num_comment_pages' => <number>,
        'queried_object'        => <Object of WP_Post/WP_Term>
    ] );

## Examples:

Mocking is_category:

    $this->mock->wp( [
        'is_category' => true,
        'query_vars'  => [
            'cat' => 'category-slug',
        ],
    ] );

Mocking is_tag:

    $this->mock->wp( [
        'is_tag' => true,
            'query_vars' => [
                'tag' => 'tag-slug', // or 'tag_id' => <integer>
            ],
    ] );

Mocking is_archive:

    $this->mock->wp( [
        'is_archive'     => true,
        'queried_object' => $wp_term,
    ] );

Mocking is_post_type_archive:

    $this->mock->wp( [
        'is_post_type_archive' => true,
        'query_vars' => [
            'post_type' => [ 'post' ],
        ]
    ] );

Mocking is_author:

    $this->mock->wp( [
        'is_author' => true,
        'query_vars' => [
            'author' => $user_id,
        ]
    ] );
