<?php
// データベース接続情報
$host = 'localhost';
$db = 'sport';
$user = 'root';
$password = 'root';

// データベースに接続
$conn = new PDO("mysql:host=$host;dbname=$db", $user, $password);

// タイムゾーンを設定
date_default_timezone_set('Asia/Tokyo');

// 投票結果のリセット処理 1分間ボタンが押せなくなる
if (date('H:i') === '16:34') {
    // 投票数をゼロにリセットするクエリを実行
    $resetQuery = "TRUNCATE TABLE votes";
    $resetStmt = $conn->prepare($resetQuery);
    $resetStmt->execute();

    // 投票履歴を削除するクエリを実行
    $deleteQuery = "TRUNCATE TABLE votes_history";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->execute();
}

// スポーツの一覧を取得
$query = "SELECT * FROM sports";
$stmt = $conn->prepare($query);
$stmt->execute();
$sports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// フォームが送信された場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedSport = $_POST['sport'];

    // 投票履歴をチェック
    $userIp = $_SERVER['REMOTE_ADDR'];
    $query = "SELECT * FROM votes_history WHERE user_ip = :user_ip";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_ip', $userIp);
    $stmt->execute();
    $voteHistory = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$voteHistory) {
        // 投票結果をデータベースに保存
        $query = "INSERT INTO votes (sport_id) VALUES (:sport_id)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':sport_id', $selectedSport);
        $stmt->execute();

        // 投票履歴を保存
        $query = "INSERT INTO votes_history (user_ip, sport_id) VALUES (:user_ip, :sport_id)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_ip', $userIp);
        $stmt->bindParam(':sport_id', $selectedSport);
        $stmt->execute();
    }

    // ページをリロードして再投稿を防止するためのリダイレクト
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 投票結果の取得とランキングの作成
$query = "SELECT sport_id, COUNT(*) AS count FROM votes GROUP BY sport_id ORDER BY count DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$voteResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ランキングデータの生成
$ranking = [];
$rank = 1;
foreach ($voteResults as $result) {
    $sportId = $result['sport_id'];
    $count = $result['count'];

    $sportName = "";
    foreach ($sports as $sport) {
        if ($sport['id'] == $sportId) {
            $sportName = $sport['name'];
            break;
        }
    }

    $ranking[] = [
        'rank' => $rank,
        'sportName' => $sportName,
        'count' => $count
    ];

    $rank++;
}


// 投票済みかどうかをチェック
$userIp = $_SERVER['REMOTE_ADDR'];
$query = "SELECT * FROM votes_history WHERE user_ip = :user_ip";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_ip', $userIp);
$stmt->execute();
$voteHistory = $stmt->fetch(PDO::FETCH_ASSOC);

// データベース接続のクローズ
$conn = null;
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1 shrink-to-fit=no">
    <meta name="description" content="株式会社MIST solution - トップページ 株式会社ミストソリューションは、異なった業界との接点を持つことで化学反応を起こし、
    幅広いニーズにより的確にお応えできる、常に進化しているIT企業です。">
    <meta name="keywords" content="株式会社ミストソリューション,ミストソリューション,MISTsolution,ミスト" />
    <meta name="copyright" content="©株式会社MIST solution All Rights Reserved.">
    <meta name="format-detection" content="telephone=no">
    <!-- OGP -->
    <meta property="og:url" content="https://www.mistnet.co.jp">
    <meta property="og:title" content="株式会社MIST solution | WEBサイト"/>
    <meta property="og:site_name" content="株式会社MIST solution | WEBサイト">
    <meta name="og:description" content="株式会社MIST solution - トップページ 株式会社ミストソリューションは、異なった業界との接点を持つことで化学反応を起こし、
    幅広いニーズにより的確にお応えできる、常に進化しているIT企業です。">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ja-JP">
    <meta property="og:image" content="assets/images/mist-ogp.jpg">
    <meta name="twitter:card" content="summary"/>
    <!-- favicon -->
    <link rel="icon" href="img/favicon.ico">    
    <title>テスト</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script src="../js/newcomer.js"></script>
    <link rel="stylesheet" href="../css/reset.css">
    <!-- <link rel="stylesheet" href="../css/newcomer.css"> -->
    <link rel="stylesheet" href="../css/common.css">
</head>

<body>
    <div class="wrapper">
        <div class="text-align">
            <p class="border-bottom font-style-title ">質問</p>
            <p class="title font-style-words border-line">「あなたの好きなスポーツは何ですか？」</p>
            <p>今日の人気スボーツランキング<br>上位5つ</p>
        </div>

        <?php if (!empty($ranking) && $voteHistory) : ?>
            <div class="ranking">
                <?php for ($i = 0; $i < min(5, count($ranking)); $i++) : ?>
                    <?php $rankData = $ranking[$i]; ?>
                    <div class="bar-graph text-align">
                        <p class="rank"><span><?php echo $rankData['rank']; ?></span>位</p>
                        <p class="sportName"><?php echo $rankData['sportName']; ?> (<?php echo $rankData['count']; ?>票)</p>
                    </div>
                <?php endfor; ?>
            </div>
            <?php else : ?>
                <p class="ranking">投票するとランキングが表示されます。</p>
            <?php endif; ?>


        <div class="text-align">
            <p>「学生時代していた。」もしくは、「個人でしていた。」など、該当するスポーツを下記からお選びください。（※複数されていた方は、一番長く在籍していたスポーツをお選びください。）
            <div class="vote">
                <?php if ($voteHistory) : ?>
                    <p>既に投票済みです。</p>
                <?php else : ?>
                    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <?php foreach ($sports as $sport) : ?>
                            <button class="sport-button" type="submit" name="sport" value="<?php echo $sport['id']; ?>">
                                <?php echo $sport['name']; ?>
                            </button>
                        <?php endforeach; ?>
                    </form>
                <?php endif; ?>
            </div>
            <P>
                エンジニアに何故スポーツ？と思う方もいるかもしれませんが、エンジニアはスポーツで培った個々のポジションの役割、チームワークなど、今回社員になったSESのルーキーたちは、
                皆スポーツをしていて、現在の業務や仕事に取り組む際の姿勢のベースになっています。エンジニアの現場経験がなかったり、経験が短期だったとしても、実際の現場では人間力も
                強い武器になってきます。
            </p>
        </div>
        <div class="newcomer__title">
            <P class="border-bottom">ses</P>
            <h2>
                「世の中に求められている技術！<br>
                必要とされているから<br>
                困難であっても頑張れる！」
            </h2>
            <P>新しい同志たち</p>
        </div>
    </div>
    </div>

    <section id="ict-link" class="section__contents anchor center">
        <div class="contents__bg">
            <div class="contents__change-button">
                <a id="ict" class="change-button__ict" href="#">
                    <h2 class="tab__title"><span>東京</span>所属<br>(神田本社)<br></h2>
                    <p>着任：関東エリア</p>
                </a>
                <a id="lbd" class="change-button__lbd" href="#">
                    <h2 class="tab__title"><span>高知</span>所属<br>(高松支店)</h2>
                    <p>着任：四国エリア</P>
                </a>
            </div>
            <div class="contents__ict container" id="target2">
            <img src="./image/pexels-pierre-blaché-3007325.jpg" alt="サンプル画像">
                <div class="contents">
                    <h3 class="contents__title">神田本社</h3>
                </div>
                <nav class="detail">
                    <p>こんにちわ</p>
                </nav>

                <button class="newcomer-btn">もっと見る</button>
            </div>
            <div class="contents__lbd" id="target">
                <div class="contents">
                    <h3 class="contents__title">高松支店</h3>
                </div>
            </div>
        </div>
    </section>
        
    <P>aaa</p>
    




</body>

</html>  
    