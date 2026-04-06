# Manual do Sistema - Clínica Go

Bem-vindo(a) ao seu novo sistema inteligente de atendimento!
Este painel gerencia as suas conversas no WhatsApp com a ajuda de Inteligência Artificial para não perder mais nenhum lead.

## Instalando e Iniciando (Primeio Acesso)

Você não precisa de conhecimentos técnicos! O sistema vem pré-configurado.

1. Baixe e instale o **Docker Desktop**:
   - [Baixar para Windows](https://www.docker.com/products/docker-desktop)
   - [Baixar para Mac](https://www.docker.com/products/docker-desktop)
2. Após instalar, certifique-se de abri-lo (o ícone de uma baleia deve aparecer na bandeja do sistema).
3. Dentro desta mesma pasta onde você encontrou este manual, dê um duplo clique no arquivo:
   - **Windows:** `start_windows.bat`
   - **Mac/Linux:** `start_linux.sh` (pelo terminal: `./start_linux.sh`)

Isso iniciará o processo de instalação! A primeira execução pode levar alguns minutos enquanto ele baixa os componentes, mas as próximas serão quase instantâneas.

---

## Acessando o Painel de Controle

Após o script informar que tudo foi concluído (verá na tela preta a mensagem de sucesso), acesse no seu navegador preferido:

🔗 **Endereço do Painel:** [http://localhost:3000](http://localhost:3000)

**Acesso Padrão:**
- **E-mail:** `admin@admin.com`
- **Senha:** `admin`

---

## Configurando seu Sistema (Passo a Passo)

Siga os seguintes passos usando a aba de configurações dentro do próprio sistema Web. **Não é necessário mexer em nenhum código.**

### Passo 1: Informar qual IA ele deve usar
Vá na barra lateral > `Configurações` > Aba `IA`.
Cole sua chave (exemplo: Gemini API Key ou Groq API Key) para que o robô consiga "pensar" as respostas usando o modelo de linguagem que preferir.

### Passo 2: Conectar ao seu WhatsApp
Vá na barra lateral > `Configurações` > Aba `WhatsApp`.
Clique no botão roxo de **QR Code** e escaneie com o aparelho de celular que vai responder as mensagens no momento (Aparelhos Conectados).

### Passo 3: Iniciar a sessão
Na mesma página do QR Code, basta apertar o botão redondo Verde (Play) para que o sistema comece a receber e analisar e responder suas mensagens automaticamente!

---

## Ajuda Extra

- **Dados sumiram?** Fique calmo. Todo histórico do banco e configurações são salvos permanentemente em "Volumes" isolados. Se reiniciar o computador, basta executar o `.bat` novamente que os arquivos carregam.
- Se encontrar lentidez, confira na aba Analytics qual IA tem o melhor Tempo de Resposta.
