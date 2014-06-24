<?php
/**
 * @package Include/help/ja
 */
?>
<h1>連続抑制回数</h1>

<br>
<br>

連続抑制回数は、状態変化を繰り返すようなデータを扱う場合に利用します。
これを設定することにより、Pandora FMS にオリジナルの状態から連続何回データが変化した状態にあるかの判断をさせることができます。
<br><br>
例えば、あるホストへの ping でパケットロスがあったとします。
このような場合、つぎのようなモニタ結果となります。
<br>
<pre>
 1  
 1  
 0  
 1  
 1  
 0  
 1  
 1  
 1 
</pre>
<br>
しかしながら、ホストは正常稼働中だとすると、複数回連続 NG であった場合のみ障害状態であると認識して欲しい場合があります。
つまり、上記は障害状態とみなさないという判断です。
たとえば、連続抑制回数を 3 に設定すると、次のような場合、
<pre>
 1  
 1  
 0  
 1  
 0  
 0  
 0  
 </pre>
<br>
次回さらに 0 が続いた時点でノードダウンと認識します。(0 が 3回まではOK、4回目でNG)
<br>
このように、連続抑制回数の設定を行うことにより 1 回のみの変化では障害とみなさない設定を実現できます。
この機能は、すべてのモジュールにおいて実装されています。

<br><br>
