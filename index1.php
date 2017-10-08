<?php
/**
 * Created by PhpStorm.
 * User: gendos
 * Date: 9/18/17
 * Time: 18:52
 */
//ini_set('display_errors', 1);
$filename = __DIR__.'/data.txt';
$censoredFilename = __DIR__.'/censored.txt';
// Массив содержит все комментарии
$comments = unserialize(file_get_contents($filename));
// Слова которые мы должны фильтровать
$censored = explode(
    PHP_EOL,
    file_get_contents($censoredFilename)
);
// Строка с сообщением об ошибка
$errors = [];
$flag = true;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Логика оработки запроса
    $author = htmlspecialchars($_POST['author']);
    $mail = htmlspecialchars($_POST['email']);
    $comment = htmlspecialchars($_POST['comment']);
    if ($author && $comment && $mail) {
        if (!empty($comments)) {
            $flag = !(in_array ($mail, array_column($comments, 'email')));//  $flag = !array_search($mail, array_column($comments, 'email'));
        }
        if ($flag == true) {
            $comments[] = [
                'date' => date('H:i:s d.m.Y'),
                'timestamp' => time(),
                'author' => $author,
                'email' => $mail,
                'comment' => $comment,
            ];
            file_put_contents($filename, serialize($comments));
        }
        else {
            $errors[] = "Человек с такиим e-mail уже заполнял форму!";
        }
    }
    else {
        $errors[] = "Форма заполнена некорректно";
    }
}

if (!empty($comments)) {
    usort($comments, function ($a, $b) {
        return (isset($a['timestamp']) && $a['timestamp'] > $b['timestamp']) ? -1 : 1;
    });
}
// Постраничная навигация
$commentsPerPage = 5;
$first = 1;
$last = ceil(count($comments)/$commentsPerPage);
$disabled_style =' class="disabled" ';
$next = 2;
if (isset($_GET['p']) && $_GET['p'] > 1 && $_GET['p']<=$last) {
    $currentPage = (int) $_GET['p'];
}
else $currentPage = 1;
// Вырезать нужные комментарии из $comments
$comments_show = array_slice($comments,($currentPage-1)*$commentsPerPage, $commentsPerPage);
?>
<DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
        <title>Comments</title>
    </head>
    <body>

    <div class="container">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <h2>Поделитесь вашим мнением</h2>

                <?php
                    if (!empty($errors)):?>
                        <div class="alert alert-danger">
                            <?= implode('<br>', $errors)?>
                        </div>
                <?php
                    endif;
                ?>
                <form method="post">
                    <div class="form-group">
                        <label for="author">Ваше имя:</label>
                        <input type="text" class="form-control"
                               name="author" id="author" required
                               placeholder="Имя пишите здесь"
                               value="Username">
                    </div>

                    <div class="form-group">
                        <label for="email">Ваш e-mail:</label>
                        <input type="text" class="form-control"
                               name="email" id="email" required
                               placeholder="E-mail пишите здесь"
                               value="test@test.mail" >
                    </div>

                    <div class="form-group">
                        <label for="comment">Ваше мнение:</label>
                        <textarea name="comment" class="form-control"
                                  id="comment" required
                                  placeholder="Comment пишите здесь" ></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Отправить
                    </button>
                </form>
            </div>
            <div class="col-md-6 col-md-offset-3">
                <?php
                // Вывод комметариев
                                foreach ($comments_show as $comment):
                                    // Убираем нежелательные слова из полей
                                    foreach (['author', 'comment'] as $key):
                                        $comment[$key] = str_ireplace(
                                            $censored,
                                            '[censored]',
                                            $comment[$key]
                                        );
                                    endforeach;
                                    ?>
                                    <div class="panel panel-success">
                                        <div class="panel-heading">
                                            <?= 'Name: '.$comment['author'].'   E-mail: '.$comment['email']?>
                                            <span><?= $comment['date']?></span>
                                        </div>
                                        <div class="panel-body">
                                            <?= $comment['comment']?>
                                        </div>
                                    </div>
                                    <hr>
                                    <?php
                                endforeach;

                ?>
                <?php  //// Вывод ссылок постраничной навигации
                ?>
            </div>
        </div>
    </div>
                    <div class="pagination">
                        <a  <?=($currentPage == 1)?$disabled_style:''; ?> href="?p=<?= $first ?>"><?= ' << ' ?></a>
                        <a  <?=!($currentPage > 1)?$disabled_style:''; ?> href="?p=<?= ($currentPage-1) ?>"><?= ' < ' ?></a>
                    <?php
                             if($currentPage > $next+1 && $currentPage < ($last-$next)){
                                 ?>
                                        <a href="?p=<?= $currentPage-$next-1 ?>"><?= ' ... ' ?></a>
                                 <?php
                                        for($i=$currentPage-$next; $i<=$currentPage+$next; $i++){
                                        ?>
                                        <a  <?= ($i == $currentPage)?$disabled_style:''; ?>  href="?p=<?= $i ?>"><?= $i ?></a>
                                        <?php
                                    }
                                 ?>
                                    <a href="?p=<?= $currentPage+$next+1 ?>"><?= ' ... ' ?></a>
                                 <?php
                             }
                             elseif($currentPage <= $next){
                                    for($i = 1; $i <= $currentPage + $next; $i++){
                                        if ($i<=$last){
                                            ?>
                                            <a  <?= ($i == $currentPage)?$disabled_style:''; ?>  href="?p=<?= $i ?>"><?= $i ?></a>
                                            <?php
                                        }

                                }
                                     ?>
                                          <a <?= ($i <= $last)?'':$disabled_style; ?>  href="?p=<?= $currentPage+$next+1 ?>"><?= ($i <= $last)?' ... ':'' ?></a>
                                     <?php
                             }
                             else{
                                         ?>
                                         <a  <?= ($currentPage > $first+$next)?'':$disabled_style; ?> href="?p=<?= $currentPage-$next-1 ?>"><?= ($currentPage > $first+$next)?' ... ':'' ?></a>
                                         <?php
                                    for($i = $currentPage - $next; $i <= $last; $i++){
                                        ?>
                                        <a  <?= ($i == $currentPage)?$disabled_style:''; ?>  href="?p=<?= $i ?>"><?= $i ?></a>
                                        <?php
                                }
                             }
                    ?>
                        <a  <?=!($currentPage < $last)?$disabled_style:''; ?> href="?p=<?= ($currentPage+1) ?>"><?= ' > ' ?></a>
                        <a  <?=($currentPage == $last)?$disabled_style:''; ?> href="?p=<?= ($last) ?>"><?= ' >> ' ?></a>
                    </div>
                <?php
                ?>

    <style type="text/css">
        a {
            padding: 5px 5px;
        }
        a.disabled {
            pointer-events: none;
            cursor: default;
            color: #999;
        }
        .pagination {
            margin: 0px auto;
            display: table;
        }
    </style>


    </body>
<?php
?>