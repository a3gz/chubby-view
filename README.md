# Chubby View
PHP Renderer for [Chubby](https://github.com/a3gz/chubby).

Chubby View is a PHP renderer that facilitates a very handy way of rendering views with Slim. 

**A template**

    class DefaultTemplate extends \Chubby\View\Template 
    {
        /**
        * @var array
        */
        protected $components = [
            'header'    => 'src/app/views/components/header.php',
            'footer'    => 'src/app/views/components/footer.php',
        ];

        /**
        * @var string 
        */
        protected $template = 'src/app/views/templates/default-template.php';
    } // class

`src/app/views/templates/default-template.php`

    <!DOCTYPE html>
    <html>
        <head>
            <meta charset="utf-8">

            <chubby-styles></chubby-styles>
        </head>
        
        <body>
            <?php $this->render('header'); ?>
            <?php $this->render('content'); ?>
            <?php $this->render('footer'); ?>

            <chubby-scripts></chubby-scripts>
        </body>
    </html>

**How to use** 

    $tpl = new \Templates\DefaultTemplate('path/to/templates');

    // The component path here is relative, 
    // to th path/to/template given in the constructor 
    $tpl->define('content', 'src/app/views/components/hello')
        ->setData(['name' => $name])
        ->write( $response );

    // It's also possible to use aboslute paths by adding a leading 
    // slash: 
    $tpl->define('content', '/abs/path/to/src/app/views/components/hello')
        ->setData(['name' => $name])
        ->write( $response );


`src/app/views/components/hello.php`

    <chubby-scripts>
        <script>
        console.log('Hello', '<?php echo $this->name; ?>');
        </script>
    </chubby-scripts>

    <chubby-styles>
        <style>
            .hello strong {
                color: blue; 
                font-size: 16px;
            }
            .bye strong {
                color: green;
            }
        </style>
    </chubby-styles>

    <div class="hello">
        <strong><?php echo "Hello {$this->name}"; ?></strong>
    </div>

    <div class="bye">
        <strong><?php echo "Bye"; ?></strong>
    </div>

**Resulting HTML file**

    <!DOCTYPE html>
    <html>
        <head>
            <meta charset="utf-8">

            <style>
                .hello strong {
                    color: blue; 
                    font-size: 16px;
                }
                .bye strong {
                    color: green;
                }
            </style>

        </head>
        
        <body>
            <header>
                <h1>Welcome to Chubby 2</h1>
                <strong>An application template for Slim 3</strong>
            </header>        

            <div class="hello">
                <strong>Hello world</strong>
            </div>

            <div class="bye">
                <strong>Bye</strong>
            </div>

            <footer>
                by <a href="https://www.roetal.com">Alejandro Arbiza</a>
            </footer>
            
            <script>
                console.log('Hello', 'world');
            </script>
        </body>
    </html>
