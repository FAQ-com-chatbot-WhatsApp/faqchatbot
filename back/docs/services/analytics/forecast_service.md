
# Feature: ForecastService

## 1. Description

The `ForecastService` is a predictive analysis and forecasting component designed to extract insights from historical data and predict future trends. It operates in a "hybrid" mode: if Machine Learning libraries like `scikit-learn` and `numpy` are installed, it uses more sophisticated models; otherwise, it resorts to basic statistical analysis, ensuring that the core functionality is always available.

Its main capabilities include:
-   Predicting message demand.
-   Calculating the probability of a lead's conversion.
-   Detecting anomalies in time series.
-   Recommending the best time to re-engage a lead.

## 2. Architecture and Design

-   **Graceful Degradation:** The architecture checks for the availability of ML dependencies (`numpy`, `scikit-learn`) at initialization. If they are absent, the service remains functional, using heuristic and statistical methods as a fallback. This makes the service resilient and easy to deploy in restricted environments.
-   **Hybrid Models:**
    -   **With ML:** Uses `numpy` for more accurate statistical calculations (mean, standard deviation) and `scikit-learn` for models like Random Forest (although the current implementation uses a heuristic score, the structure is ready for a trained model).
    -   **Without ML:** Relies on pure Python implementations for statistical calculations and a heuristic scoring system based on business rules to predict conversion.
-   **Statistical Analysis:** For anomaly detection, it uses the Z-score method, a robust statistical technique for identifying data points that deviate significantly from the mean.

## 3. Data Structure

The service operates with well-defined data structures:
-   **Historical Data:** Lists of dictionaries, usually containing a date (`"date"`) and a value (`"volume"` or `"value"`).
-   **Lead Data:** A dictionary containing metrics such as `maturity_score`, `message_count`, `response_time_avg_seconds`, etc.
-   **Outputs:** The outputs are always structured dictionaries with a `"status"` field (`"success"` or `"error"`) and the analysis results, such as the conversion probability, a list of anomalies, or the time recommendation.

## 4. Dependencies and Integrations

-   **Optional Dependencies:** `numpy`, `scikit-learn`. Their absence does not break the service.
-   **`MetricsService` (implied):** This service likely depends on a `MetricsService` (or analytics repositories) to obtain the historical data needed for forecasts and analyses.
-   **Analytics Dashboard (Frontend):** The frontend consumes the results of this service to display forecast charts, anomaly alerts, and conversion probability scores for clinic administrators.

## 5. Use Cases

-   **Resource Planning:** The clinic uses `forecast_demand` to predict the volume of messages in the coming weeks, helping to scale the support team.
-   **Lead Prioritization:** `predict_lead_conversion_probability` is used to create a list of "hot" leads, allowing agents to focus their efforts on those most likely to schedule a consultation.
-   **System Health Monitoring:** `detect_anomalies` monitors metrics like the bot's response time. If an anomaly (a spike in response time) is detected, an alert can be generated for the technical team.
-   **Re-engagement Campaigns:** `recommend_reengagement_time` helps automate follow-ups with inactive leads, sending a message at the time they are most likely to respond.

## 6. Security

-   The service itself does not directly handle authentication or sensitive data, but it consumes data that must be accessed securely. Access to endpoints that use this service should be restricted to authorized users (e.g., administrators).

## 7. Performance

-   Performance varies drastically depending on the presence of ML libraries. With `numpy`, calculations are vectorized and much faster.
-   The operations are designed to be asynchronous (`async`), ensuring that potentially time-consuming analyses do not block the server.

## 8. Testability

-   The logic can be tested by providing input datasets (lists of dictionaries) and verifying that the output matches the expected result, both in "ML" mode and "basic" mode.
-   Graceful degradation can be tested by manipulating the import of optional libraries.

## 9. Error Handling

-   **Insufficient Data:** If the historical data provided is insufficient for a meaningful analysis, the service returns a clear error message.
-   **Calculation Errors:** Any exception during statistical or ML calculations is caught, logged, and encapsulated in an error response with `"error"` status, preventing the system from crashing.
-   **Division by Zero:** The anomaly detector checks if the standard deviation is zero to avoid division-by-zero errors in constant data series.
