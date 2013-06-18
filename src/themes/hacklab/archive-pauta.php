<?php get_header(); ?>

<div id="delibera">
	<div id="container">
		<div id="content" role="main">
			<?php
			
			get_delibera_header();
			//delibera_filtros_gerar();
            
			?>
            <div class="clearfix">
                <div class="filters widget-area alignleft">
                    <h2>Filtros</h2>
                    <ul class="dates">
                        <li>
                            <label class="date">Data inicial</label>
                            <input type="date"/>
                        </li>
                        <li>
                            <label class="date">Data Final</label>
                            <input type="date"/>
                        </li>
                    </ul>
                    <ul class="status">
                        <li><span>Proposta</span></li>
                        <li><span class="selected">Discussão</span></li>
                        <li><span>Relatoria</span></li>
                        <li><span>Votação</span></li>
                        <li><span>Encerrada</span></li>
                    </ul>
                    <ul>
                        <li><label class="checkbox"><input type="checkbox"/>Tema #1</label></li>
                        <li><label class="checkbox"><input type="checkbox"/>Tema #2</label></li>
                        <li><label class="checkbox"><input type="checkbox"/>Tema #3</label></li>
                        <li><label class="checkbox"><input type="checkbox"/>Tema #4</label></li>
                        <li><label class="checkbox"><input type="checkbox"/>Tema #5</label></li>
                        <li><label class="checkbox"><input type="checkbox"/>Tema #6</label></li>
                        <li><label class="checkbox"><input type="checkbox"/>Tema #7</label></li>
                        <li><label class="checkbox"><input type="checkbox"/>Tema #8</label></li>
                        <li><label class="checkbox"><input type="checkbox"/>Tema #9</label></li>
                        <li><label class="checkbox"><input type="checkbox"/>Tema #10</label></li>
                        <li><label class="checkbox"><input type="checkbox"/>Tema #11</label></li>
                    </ul>
                    <div class="textright">
                        <button class="btn">Filtrar</button>
                    </div>
                </div>					
    			<div id="lista-de-pautas" class="site-content alignright">
    				<?php load_template(dirname(__FILE__) . '/delibera-loop-archive.php', true); ?>
    			</div>
    		</div>
		</div>
	</div>
</div>

<?php get_footer(); ?>
