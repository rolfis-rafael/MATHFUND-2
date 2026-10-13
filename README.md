O Mathfund 2 é um projeto de site gameficado com jogos e desafios matemáticos, desenvolvido para tornar o aprendizado mais interativo e divertido, é um aprimoramento do primeiro mathfund, mas com foco em vestibulares e estimular a juventude a gostar de estudar.

Acesse o Projeto nesse link:
mathfund.gamer.free

O projeto Está hospedado no InfinityFree

Sobre o Projeto:

MATHFUND 3.2 — ALUNO NOTA 10 E TROFÉUS

ABRIR NO XAMPP
1. Extraia o ZIP.
2. Copie a pasta mathfund para C:\xampp\htdocs\
3. Inicie o Apache no XAMPP.
4. Abra http://localhost/mathfund/ e clique em qualquer lugar para começar.

O caminho deve terminar em C:\xampp\htdocs\mathfund\index.php.
Use PHP 8 ou superior. Não precisa de internet, Node ou MySQL no modo padrão.
Para ver o código, abra a pasta mathfund no VS Code.

ATUALIZAR A SUA VERSÃO ANTERIOR SEM PERDER O PROGRESSO
1. Faça uma cópia da pasta atual como backup.
2. Copie index.php, api.php e as pastas app/ e assets/ desta entrega para a
   instalação atual, substituindo os arquivos correspondentes.
3. Preserve storage/, data/ e config.local.php da sua instalação.
4. Atualize também README.md, LEIA-ME.txt e tests/, se desejar.
5. Recarregue o navegador com Ctrl+F5.

Esta atualização MUDA as regras do jogo: copiar somente assets/ não basta.
As estrelas e os desbloqueios anteriores continuam válidos. Uma partida
antiga em andamento conserva suas regras até terminar. O formato dos dados
dos jogadores não mudou. Se usa MySQL, importe o novo database.sql uma vez
para criar a tabela mf_questions com as 99 perguntas. Não apaga jogadores
nem substitui questões já editadas.
O ZIP não inclui os arquivos pessoais de jogadores do pacote enviado.

NOVIDADES 3.2
- Nota de 0 a 10 por fase, média de cada unidade e média das 50 fases.
  Conta a melhor nota; fases não jogadas valem zero.
- 38 troféus: comum, raro, espetacular e platina, usando as imagens enviadas.
- Pop-up a cada conquista; os troféus anteriores são reconhecidos pelas estrelas.
- Platina: conquiste os outros 37 troféus, incluindo 3 estrelas em todo o jogo.
- Perfil com troféus, boletim e minutos jogados; ranking por pontos de troféu.
- Tempo contado em partidas com a aba visível, a partir desta versão.
- Caminho tracejado e raposinha marcando a fase atual.
- Raposa grande nos tutoriais, cerca de um terço da tela.
- Abertura não repete ao atualizar a página na mesma aba.

BANCO DE PERGUNTAS
O arquivo database.sql contém as 99 perguntas completas.
Para usar: inicie MySQL no XAMPP, importe o SQL no phpMyAdmin e configure
config.local.php usando config.example.php. As instruções estão no README.
No modo MySQL, o jogo lê e edita as questões em mf_questions.
O modo padrão em arquivos continua disponível só com o Apache.

RECURSOS MANTIDOS
- Primeira unidade: 2 perguntas por fase. Outras: 3. Inclui chefes.
- Continuam 10 fases por unidade, 50 no total.
- Fases comuns: acerte 1 de 2 ou 2 de 3 para avançar.
- Chefes nas fases 5 e 10: acerte todas. Duas vidas, sem resgate.
- Primeiro chefe: 12 traços e 160 caracteres por folha.
- Chefe final: 8 traços e 120 caracteres por folha.
- Tela inicial com a logo completa e entrada por clique em qualquer lugar.
- Sons removidos. Carregamento circular com giro CSS independente.
- Resultado com a raposinha mostrando um atributo por vez.
- Em Como jogar, escolha um modo e experimente o tutorial da raposa.
- Mini explicações ao passar o mouse ou navegar pelo teclado.
- Transições suaves, mantendo as cores e a estrutura.

As 99 questões enviadas foram preservadas. A campanha agora é curta; os
outros modos continuam usando o banco da série. Uma partida já em andamento
conserva suas perguntas e regras até acabar. Inicie outra para usar as novas.
O caderninho fica neste navegador e as folhas dos chefes são separadas por
partida. Desfazer ou limpar libera espaço no limite da folha.

DADOS E TESTES
O progresso e os tutoriais vistos ficam no servidor; o caderninho fica neste
navegador. Registre uma conta para entrar em outro aparelho no mesmo servidor.
O visitante depende do cookie do navegador para reconhecer seu progresso.
Banco MySQL opcional: instruções no README.md. O modo em arquivos já funciona.
Regras, API PHP e interações simuladas foram verificadas. O desenho, o layout
real e a conexão MySQL ainda precisam de conferência no seu XAMPP.
