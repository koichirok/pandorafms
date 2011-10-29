<h1>予測日時</h1>


<p>予測日時は、将来モジュールデータが指定した範囲になる日時を予測します。最小二乗法を利用しています。</p>

<p>
<b>間隔(Period)</b>: 予測に使うデータの時間間隔
</p>
<p>
<b>データ範囲(Data Range)</b>: 予測日時を返すモジュールデータの範囲
</p>

<p>以下の例では、disk_temp_free というモジュールで、二か月間を選択しデータ範囲を [5-0] として、04 Dec 2011 18:36:23 が出力されています。</p>
<p>これはグラフ表示バージョンです。 </p>

<?php html_print_image("images/help/prediction_date.png", false, array('height' => '210')); ?>
