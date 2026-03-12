# Dashboard Analytics Plan

## 📊 Storytelling Structure

O dashboard conta a **história da operação** em 5 níveis:

### 1️⃣ **AGORA** - Real-time Status (Hero Section)
> "O que está acontecendo NESTE momento?"

**Endpoint:** `GET /api/v1/analytics/realtime/dashboard`
- ⚡ Conversas ativas
- 📈 Mensagens por minuto
- ⏱️ Tempo médio de resposta
- 🤖 Taxa de resolução do bot
- 🚨 Alertas de performance (latência alta, taxa de erro)

**Componentes:**
- Live stats cards (auto-refresh 30s)
- Active conversations list
- Performance alerts banner

---

### 2️⃣ **HOJE** - Dashboard KPIs (Snapshot)
> "Como está a performance HOJE comparado com o período?"

**Endpoint:** `GET /api/v1/analytics/dashboard?period=30d`
- 👥 Total de leads
- ✅ Leads convertidos
- 📊 Taxa de conversão (%)
- 💬 Total de conversas
- ⚡ Conversas ativas
- 📨 Total de mensagens
- ⏱️ Tempo médio de resposta

**Componentes:**
- KPI cards com sparklines (mini gráficos de tendência)
- Comparação com período anterior (+12%, -5%, etc)

---

### 3️⃣ **JORNADA** - Funil de Conversão (Lead Journey)
> "Como os leads estão progredindo?"

**Endpoints:**
- `GET /api/v1/analytics/conversion-funnel` - 5 etapas + drop-off
- `GET /api/v1/analytics/conversion/time-to-conversion-extended` - Tempo até converter (p50, p75, p90, p95)
- `GET /api/v1/analytics/conversion/lost-leads` - Leads perdidos por faixa de maturidade
- `GET /api/v1/analytics/conversion/by-source` - Conversão por origem (direct, group)

**Dados:**
1. **Funil:** created → engaged → qualified → interested → converted
2. **Drop-off:** Percentual de abandono em cada etapa
3. **Tempo:** Quanto leva para converter (mediana, percentis)
4. **Perdas:** Onde estamos perdendo leads (maturity score)

**Componentes:**
- Funnel chart (visual de funil)
- Time to conversion chart (percentis)
- Lost leads breakdown (maturity ranges)
- Conversion by source (pie chart)

---

### 4️⃣ **EFICIÊNCIA** - Performance do Bot (Automation)
> "O bot está funcionando bem?"

**Endpoints:**
- `GET /api/v1/analytics/bot-autonomy` - Taxa de autonomia (% sem handoff)
- `GET /api/v1/analytics/performance/bot-response-time` - Latência do bot (p50, p95, p99)
- `GET /api/v1/analytics/performance/handoff-rate` - Taxa de transferência para humano
- `GET /api/v1/analytics/performance/conversations-by-status` - Distribuição por status

**Dados:**
1. **Autonomia:** % de conversas resolvidas SEM humano
2. **Velocidade:** Tempo de resposta do bot (ms)
3. **Handoff:** Quando precisa de humano
4. **Status:** Distribuição (ACTIVE_BOT, ACTIVE_HUMAN, COMPLETED, etc)

**Componentes:**
- Autonomy rate gauge (0-100%)
- Response time chart (percentis)
- Handoff rate card
- Status distribution (donut chart)

---

### 5️⃣ **CONTEXTO** - Análise de Conversas (Insights)
> "QUANDO e SOBRE O QUE as pessoas falam?"

**Endpoints:**
- `GET /api/v1/analytics/performance/peak-hours` - Horários de pico
- `GET /api/v1/analytics/conversation/activity-heatmap` - Heatmap (dia x hora)
- `GET /api/v1/analytics/conversation/topics` - Topics mais discutidos
- `GET /api/v1/analytics/conversation/keywords` - Palavras-chave mais frequentes
- `GET /api/v1/analytics/conversation/sentiment` - Análise de sentimento (pos/neg/neutral)

**Dados:**
1. **Quando:** Dia da semana + hora (heatmap visual)
2. **Sobre:** Topics e keywords mais comuns
3. **Como:** Sentimento das mensagens (positivo/negativo/neutro)

**Componentes:**
- Peak hours bar chart
- Activity heatmap (calendar-style: seg-dom x 0-23h)
- Topics distribution (horizontal bar)
- Keywords cloud
- Sentiment distribution (stacked bar)

---

### 6️⃣ **EVOLUÇÃO** - Tendências (Trends)
> "Como está mudando ao longo do tempo?"

**Endpoints:**
- `GET /api/v1/analytics/conversion/trend?granularity=day` - Tendência de conversão (diária/semanal/mensal)

**Dados:**
1. **Conversão:** Taxa ao longo do tempo
2. **Volume:** Leads criados vs convertidos

**Componentes:**
- Line chart (conversão ao longo do tempo)
- Area chart (volume de leads)

---

## 🎨 UI Layout Structure

```
┌─────────────────────────────────────────────────────────┐
│ [PageHeader] Dashboard                  [Period Picker] │
├─────────────────────────────────────────────────────────┤
│                                                           │
│ 🟢 AGORA - Real-time Status                             │
│ ┌─────────┬─────────┬─────────┬─────────┬─────────┐    │
│ │ Active  │ Msg/Min │ Resp    │ Bot     │ Alerts  │    │
│ │ Convs   │         │ Time    │ Rate    │         │    │
│ └─────────┴─────────┴─────────┴─────────┴─────────┘    │
│                                                           │
│ 📊 HOJE - KPIs (Comparado com período)                  │
│ ┌────────┬────────┬────────┬────────┬────────┐         │
│ │ Leads  │ Conv   │ Conv % │ Convs  │ Active │         │
│ │ +12%   │ +8%    │ +5%    │ +15%   │ -3%    │         │
│ └────────┴────────┴────────┴────────┴────────┘         │
│                                                           │
│ 🚀 JORNADA - Funil de Conversão                         │
│ ┌─────────────────────────────────────────────┐         │
│ │ [Funnel Chart: 5 stages + drop-off]        │         │
│ └─────────────────────────────────────────────┘         │
│ ┌───────────────┬───────────────┬───────────────┐       │
│ │ Time to Conv  │ Lost Leads    │ By Source     │       │
│ │ (percentis)   │ (maturity)    │ (direct/group)│       │
│ └───────────────┴───────────────┴───────────────┘       │
│                                                           │
│ 🤖 EFICIÊNCIA - Bot Performance                         │
│ ┌────────────┬────────────┬────────────┬───────────┐    │
│ │ Autonomy   │ Response   │ Handoff    │ Status    │    │
│ │ Gauge      │ Time Chart │ Rate Card  │ Donut     │    │
│ └────────────┴────────────┴────────────┴───────────┘    │
│                                                           │
│ 💭 CONTEXTO - Análise de Conversas                      │
│ ┌──────────────────┬──────────────────────────┐         │
│ │ Activity Heatmap │ Peak Hours               │         │
│ │ (seg-dom x 0-23h)│ (bar chart)              │         │
│ └──────────────────┴──────────────────────────┘         │
│ ┌──────────┬──────────┬──────────────────────┐          │
│ │ Topics   │ Keywords │ Sentiment            │          │
│ │ (horiz)  │ (cloud)  │ (pos/neg/neutral)    │          │
│ └──────────┴──────────┴──────────────────────┘          │
│                                                           │
│ 📈 EVOLUÇÃO - Tendências                                │
│ ┌─────────────────────────────────────────────┐         │
│ │ [Conversion Trend Over Time - Line Chart]  │         │
│ └─────────────────────────────────────────────┘         │
└─────────────────────────────────────────────────────────┘
```

---

## 📋 Implementation Checklist

### Phase 1: Types & Services
- [ ] Create analytics types (TypeScript interfaces from schemas)
- [ ] Create analyticsService.ts (all endpoints)
- [ ] Create useAnalytics hook (data fetching + caching)

### Phase 2: Visualization Components
- [ ] FunnelChart component
- [ ] ActivityHeatmap component
- [ ] PercentileChart component (for time metrics)
- [ ] GaugeChart component (for percentages)
- [ ] KeywordCloud component
- [ ] SentimentBar component

### Phase 3: Dashboard Sections
- [ ] RealtimeSection (hero with live data)
- [ ] KPIsSection (cards with trends)
- [ ] JourneySection (funnel + conversion)
- [ ] EfficiencySection (bot performance)
- [ ] ContextSection (conversation analysis)
- [ ] TrendsSection (evolution over time)

### Phase 4: Dashboard Page
- [ ] Integrate all sections
- [ ] Period picker (7d, 30d, 90d, custom)
- [ ] Auto-refresh for realtime data
- [ ] Loading states
- [ ] Error handling
- [ ] Empty states

### Phase 5: Polish
- [ ] Responsive layout
- [ ] Export functionality (PDF/Excel)
- [ ] Drill-down capabilities
- [ ] Tooltips and legends
- [ ] Color coding (success/warning/danger)

---

## 🎯 Key Insights to Highlight

1. **Conversion Health:** Taxa de conversão + tendência
2. **Bot Efficiency:** Autonomia do bot (meta: >80%)
3. **Response Speed:** Tempo de resposta (meta: <2s)
4. **Lead Journey:** Onde estamos perdendo leads?
5. **Peak Times:** Quando precisamos mais recursos?
6. **Conversation Quality:** Sentimento e topics

---

## 🔄 Auto-refresh Strategy

- **Realtime Dashboard:** 30 segundos
- **KPIs:** 5 minutos
- **Charts:** On-demand (user action or 15min)
- **Reports:** Cache 15min
