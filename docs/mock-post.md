# Mock WP Post

---
To mock and set the global $post variable. If no properties/arguments for post are passed, it will generate a new post with default data.

Mock a new post

    $this->mock->post( [] );

Set a mocked post if not exist:

    $this->mock->post();

Get the existing mocked post object:

    $post = $this->mock->post()-get();

Chained mocked feature:
    
    $post = $this->mock
        ->post( [] ) // create a new post
        ->is_amp() // mark it as an amp end point
        ->get(); // finally return the mocked post


Supported mocked post arguments:

    $this->mock->post( [

        // All valid post fields
        ...
        
        // Post taxonomy
        'taxonomy' => [
            'category' => [ 'cat1', 'cat2', ... ],
            'post_tag' => [ 'tag1', 'tag2', ... ],
            'vertical' => [ 'vert1', 'vert2', ... ],
        ],

        // Post meta data
        'post_meta' => [
            'key1' => 'value1',
            'key2' => 'value2',
            ...
        ],

        // PMC Post options
        'post_options' => [ 'opt1', 'opt2', ... ],

        // Custom function to call back
        'callback' => function( $post ) {
            // do additional mock for $post
        }

    ] );
