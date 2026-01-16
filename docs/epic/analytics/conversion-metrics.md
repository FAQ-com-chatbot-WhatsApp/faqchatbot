# Feature: Conversion Rate Calculation & Analytics

**Epic:** Analytics & Reporting  
**Status:** MVP Complete (Core Implemented)  
**Owner:** Backend & Analytics Team  
**Implementation:** ~100 lines (analytics/metrics_service.py)  
**Tests:** [back/tests/integration/test_conversion_reports_l2.py](back/tests/integration/test_conversion_reports_l2.py)

## What's Implemented ✅

### 1. Conversion Rate Calculation
- **calculate_conversion_rate()**: Overall conversion percentage
  - Query: Total leads created (all-time)
  - Query: Leads with status="CONVERTED" (all-time)
  - Formula: (converted / total) × 100
  - Returns: Float percentage (0-100)
  - Example: 45 converted / 100 total = 45%

### 2. Time-Period Filtering
- **by_date_range()**: Filter metrics by dates
  - Input: start_date, end_date
  - Query: Leads created within range
  - Calculate: Conversion rate for period only
  - Returns: Metrics scoped to date range

### 3. Conversion by Lead Source
- **by_source()**: Conversion rates by channel
  - Query: Leads grouped by source (Instagram, Facebook, Google Ads, WhatsApp, Organic)
  - Calculate: Conversion rate per source
  - Returns: Dict {source: conversion_rate}
  - Example: {"instagram": 45%, "facebook": 30%, "google_ads": 50%}

### 4. Conversion by Clinic
- **by_clinic()**: Conversion metrics per clinic
  - Query: Leads grouped by clinic_id
  - Calculate: Conversion rate per clinic
  - Returns: Dict {clinic_id: conversion_rate}
  - Multi-tenant: Each clinic sees only their data

### 5. Lost Lead Analysis
- **lost_leads_analysis()**: Why leads don't convert
  - Query: Leads with status="LOST"
  - Categorize reasons:
    - NO_RESPONSE: User never replied (> 7 days no message)
    - LOW_INTEREST: Score never reached 50 (dropped out early)
    - PRICE_OBJECTION: Asked price but didn't book
    - COMPETITOR_CHOICE: Mentioned competitor
    - LOST_CONTACT: Phone/email invalid
  - Returns: Distribution of loss reasons
  - Example: {no_response: 40%, price_objection: 25%, competitor: 20%, other: 15%}

### 6. Conversion Funnel
- **conversion_funnel()**: Metrics by score buckets
  - Bucket 1 (Score 0-25): NEW leads (input)
  - Bucket 2 (Score 25-50): ENGAGED (drop-off %)
  - Bucket 3 (Score 50-70): INTERESTED (drop-off %)
  - Bucket 4 (Score 70+): READY (conversion %)
  - Calculate: How many progress through each stage
  - Returns: Funnel visualization data
  - Example:
    ```
    100 leads enter at 0-25 score
    75 reach 25-50 (75% proceed)
    50 reach 50-70 (67% proceed)
    42 reach 70+ (84% proceed)
    35 actually convert (83% of ready)
    Overall: 35% conversion
    ```

### 7. Conversion Trends
- **conversion_trend()**: Conversion rate over time
  - Query: Daily/weekly/monthly conversion rates
  - Aggregate: Conversions grouped by time period
  - Calculate: Rate per period
  - Returns: Time series data (for charts)
  - Example: [
      {date: "2024-01-01", rate: 30%, count: 12},
      {date: "2024-01-02", rate: 35%, count: 15},
      {date: "2024-01-03", rate: 40%, count: 18}
    ]

### 8. Performance Comparison
- **compare_clinics()**: Benchmark between clinics
  - Query: Metrics for multiple clinics
  - Compare: Conversion rates, avg score, lost leads
  - Identify: Top performers vs underperformers
  - Returns: Ranked clinic performance

### 9. Cohort Analysis
- **cohort_analysis()**: Track cohorts over time
  - Cohort: Leads created in same week/month
  - Track: What % of cohort converts over 30/60/90 days
  - Returns: Cohort retention/conversion table
  - Example: Leads from January (cohort): 30-day conversion: 25%, 60-day: 35%, 90-day: 40%

### 10. Forecast
- **forecast_conversion()**: Predict future conversion
  - Use: Historical conversion trend
  - Calculate: Trend line (linear regression)
  - Project: Expected conversion rate next month
  - Returns: Forecast with confidence interval

## Code References

**Analytics Service:** [back/src/robbot/services/analytics/metrics_service.py](back/src/robbot/services/analytics/metrics_service.py) (~100 lines)
- `calculate_conversion_rate()` - Overall rate
- `by_source()` - Conversion by channel
- `conversion_funnel()` - Funnel metrics
- `lost_leads_analysis()` - Dropout reasons
- `conversion_trend()` - Time series

**Forecast Service:** [back/src/robbot/services/analytics/forecast_service.py](back/src/robbot/services/analytics/forecast_service.py)
- `forecast_conversion()` - ML-based prediction
- Uses: numpy, scipy for trend analysis

**Repositories:**
- [LeadRepository](back/src/robbot/adapters/repositories/lead_repository.py)
  - `count_by_status()` - Count leads per status
  - `count_by_source()` - Count per source
  - `query_date_range()` - Filter by dates

**Models:**
- [LeadModel](back/src/robbot/infra/db/models/lead_model.py)
  - status (NEW, ENGAGED, INTERESTED, READY, SCHEDULED, CONVERTED, LOST)
  - maturity_score (0-100)
  - source (Instagram, Facebook, Google Ads, etc.)
  - created_at, updated_at, converted_at

**Schemas:**
- [ConversionMetrics](back/src/robbot/schemas/analytics.py)
  - conversion_rate, total_leads, converted_count, lost_count

## Gaps ❌

| Gap | Priority | Impact |
|-----|----------|--------|
| Real-time dashboard updates | HIGH | Analytics updates every hour (not live) |
| Revenue tracking ($ per conversion) | HIGH | Cannot calculate ROI by source |
| Appointment attendance rate | MEDIUM | Cannot track no-shows vs actual attendance |
| Agent performance comparison | MEDIUM | Cannot see which agents convert best |
| Predictive lead scoring | MEDIUM | Cannot predict which leads will convert |

## Calculation Logic

### Overall Conversion Rate
```
Query leads table:
  total_leads = COUNT(*) all leads
  converted_leads = COUNT(*) WHERE status='CONVERTED'
  
conversion_rate = (converted_leads / total_leads) * 100

Example:
  total_leads = 100
  converted_leads = 35
  conversion_rate = 35%
```

### Conversion by Source
```
Query leads grouped by source:
  Source → Count → Conversion Count → Rate
  ─────────────────────────────────────────
  Instagram → 40 → 18 → 45%
  Facebook → 30 → 9 → 30%
  Google Ads → 20 → 10 → 50%
  Direct → 10 → 4 → 40%
```

### Conversion Funnel
```
Score Bucket → Lead Count → Conversion Count → Conversion %
───────────────────────────────────────────────────────────
0-25    → 100     → 80 (proceed)      → 80% retention
25-50   → 80      → 60 (proceed)      → 75% retention
50-70   → 60      → 50 (proceed)      → 83% retention
70+     → 50      → 42 (converted)    → 84% conversion

Dropout pattern:
  20% drop at 0-25 (not interested)
  25% drop at 25-50 (lost interest)
  17% drop at 50-70 (stalled)
  16% drop at 70+ (didn't book)
```

### Lost Lead Reasons
```
Query leads WHERE status='LOST':

Categorize by pattern:
  no_response: last_message_at < 7 days ago (30%)
  low_interest: max_score < 50 (25%)
  price_objection: asked price, score stayed low (20%)
  competitor: mentioned competitor (15%)
  invalid_contact: message delivery failed (10%)

Total: 100% of lost leads
```

## Integration Points

**Called by:** Dashboard endpoints
- `/api/v1/analytics/conversion-rate` - Overall rate
- `/api/v1/analytics/by-source` - Source breakdown
- `/api/v1/analytics/funnel` - Funnel visualization
- `/api/v1/analytics/lost-leads` - Dropout analysis

**Calls:**
- LeadRepository (queries)
- Database (PostgreSQL)

**Displays:**
- Real-time dashboard
- Admin reports (PDF export)
- Email summaries (weekly)

## Testing Strategy

### Unit Tests

```python
def test_calculate_conversion_rate():
    """45 converted / 100 total = 45%"""
    
def test_conversion_by_source():
    """Instagram 45%, Facebook 30%, Google 50%"""
    
def test_conversion_funnel_buckets():
    """Funnel shows correct retention at each stage"""
    
def test_lost_leads_analysis():
    """Categorizes lost leads by reason"""
    
def test_conversion_trend_daily():
    """Daily conversion rates over 30 days"""
    
def test_conversion_trend_by_date_range():
    """Filter metrics by custom date range"""
    
def test_forecast_conversion_next_month():
    """Forecast increases if trend is up"""
```

### Integration Tests

```python
def test_analytics_complete_flow():
    """Create leads → mark converted → calculate rate"""
    
def test_analytics_by_clinic_isolation():
    """Clinic A's metrics don't affect Clinic B"""
```

## Performance Considerations

**Query Optimization:**
- Use database aggregation (not Python loops)
- Indexed: status, source, created_at, clinic_id
- Cache results (1-hour TTL)

**Dashboard Updates:**
- Background job (runs hourly)
- Computes all metrics once
- Stores in analytics cache table
- Dashboard reads cache (instant)

**Scaling:**
- Millions of leads: Partition by date (monthly)
- Query only last 90 days (default)
- Historical data in archive table

## Visualization Examples

### Conversion Rate Gauge
```
┌─────────────────────┐
│  Overall Conversion │
│       35%           │
│   ████████░░░░░░░░  │
└─────────────────────┘
```

### Conversion by Source (Bar Chart)
```
Instagram   ████████████████████ 45%
Google Ads  █████████████████░░░ 50%
Facebook    ████████░░░░░░░░░░░░ 30%
WhatsApp    ███████████████░░░░░ 40%
```

### Conversion Funnel (Waterfall)
```
100 New Leads
  ↓
80 Engaged (80%)
  ↓
60 Interested (75%)
  ↓
50 Ready (83%)
  ↓
42 Converted (84%)

Dropout:
  20 (20%) lost at NEW
  20 (25%) lost at ENGAGED
  10 (17%) lost at INTERESTED
  8  (16%) lost at READY
```

### Conversion Trend (Line Chart)
```
50% ┌───────────────┐
    │       ╱╲      │
40% │      ╱  ╲    ╱│
    │     ╱    ╲  ╱ │
30% │────╱      ╲╱──│
    │   Jan  Feb  Mar
```

## Next Steps (Roadmap)

### High Priority
1. **Real-time dashboard** - Update metrics every 5 minutes
2. **Revenue tracking** - Calculate ROI by source
3. **Appointment attendance** - Track show rates vs no-shows

### Medium Priority
1. Agent performance comparison (which agents convert best)
2. Predictive lead scoring (ML model)
3. A/B testing analytics (compare playbook versions)

### Low Priority
1. Cohort analysis (retention by acquisition month)
2. Customer lifetime value (CLV) calculation
3. Churn prediction (identify at-risk leads)
