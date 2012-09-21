<?php
/**
 * @package Include/help/ja
 */
?>
<h1>マクロ</h1>

モジュールの実行(module_exec)や、プラグインパラメータにマクロを設定することができます。
<br /><br />
マクロには次の 3つのパラメータがあります。
<ul>
	<li>説明</li>
	<li>デフォルト値 (オプション)</li>
	<li>ヘルプ (オプション)</li>
</ul>

例えば、サーバで動作している apache のプロセス数を返すモジュールの設定では、次のコマンドを実行します。
<br /><br />
ps -A | grep apache2 | wc -l
<br /><br />
次のように、プロセス名はマクロで置き換えることができます。
<br /><br />
ps -A | grep _field1_ | wc -l
<br /><br />
マクロのパラメータは次のように設定します。

<ul>
	<li>説明: Process</li>
	<li>デフォルト値: apache2</li>
	<li>ヘルプ: 指定した文字列の実行プロセス数をカウントします</li>
</ul>

このコンポーネントのモジュールを設定すると、デフォルトの値が "apache2" のテキストフィールド "Process" が表示され編集することができます。また、詳細としてヘルプの内容が表示されます。
<br /><br />
マクロは、必要なだけ設定することができます。
