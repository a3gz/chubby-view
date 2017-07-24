# Chubby View
PHP Renderer for [Chubby](https://github.com/a3gz/chubby).

Chubby View is a PHP renderer that facilitates a very handy way of rendering views with Slim. 

**A template class**

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

**A template file**
    <!DOCTYPE html>
    <html>
        <head>
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">

            <chubby-styles></chubby-styles>
        </head>
        
        <body>
            <?php $this->render('header'); ?>
            <?php $this->render('content'); ?>
            <?php $this->render('footer'); ?>

            <chubby-scripts></chubby-scripts>
        </body>
    </html>

**A component**
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

**How to use 

    $tpl = new \Templates\DefaultTemplate();
    $tpl->define('content', 'src/app/views/components/hello')
        ->setData(['name' => $name])
        ->write( $response );