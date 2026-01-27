# Lease Management System - Comprehensive Evaluation Framework

## Overview

This evaluation framework provides automated testing and validation across all critical areas of the Chabrin Lease Management System:

- ✅ **Business Logic** - Lease workflow state transitions and approval processes
- ✅ **API Validation** - Response accuracy, status codes, and error handling  
- ✅ **Data Integrity** - Field validation, consistency checks, and audit logging
- ✅ **Performance** - Response time thresholds and query efficiency
- ✅ **Workflows** - End-to-end lease lifecycle execution

## Components

### Test Data
- **`evaluation_data/test_queries.json`** - 20 test scenarios covering all evaluation areas
- **`evaluation_data/test_responses.json`** - Expected responses and outcomes
- **`evaluation_data/evaluation_dataset.jsonl`** - Prepared dataset (generated during evaluation)

### Evaluators

#### 1. LeaseStateTransitionValidator
**What it validates**: Lease state transitions follow business rules

**Valid transitions**:
- `draft` → `approved`, `cancelled`
- `approved` → `printed`, `sent_digital`, `cancelled`
- `active` → `renewal_offered`, `expired`, `terminated`
- And 11+ other defined transitions

**Metrics**: 
- State transition validity
- Response outcome matching

#### 2. ApprovalWorkflowValidator
**What it validates**: Landlord approval, rejection, and resubmission logic

**Test scenarios**:
- Request approval from draft state
- Approve lease after request
- Reject lease with reason
- Verify state transitions during approval

**Metrics**:
- Approval workflow correctness
- Decision recording accuracy

#### 3. APIResponseAccuracyValidator
**What it validates**: API responses are correctly formatted with proper status codes

**Coverage**:
- HTTP status code correctness (201, 200, 422, 400, 404)
- Response body structure and data
- Error message clarity

**Metrics**:
- Status code accuracy
- Response data presence
- Outcome matching

#### 4. DataValidationValidator
**What it validates**: Required fields are validated and invalid data is rejected

**Test cases**:
- Missing required fields (monthly_rent, unit_id, etc.)
- Negative amounts
- Invalid date ranges
- Invalid state values

**Metrics**:
- Validation works correctly
- Error handling accuracy

#### 5. LeaseDataConsistencyValidator
**What it validates**: Audit logs, serial numbers, and record consistency

**Checks**:
- Audit logs created on state transitions
- Serial number uniqueness enforcement
- Approval record alignment with workflow state
- Data integrity across related records

**Metrics**:
- Data consistency validity
- Audit trail completeness

#### 6. ResponseTimeValidator
**What it validates**: API response times meet performance thresholds

**Thresholds**:
- Default: 1000ms per request
- Customizable per test scenario
- Bulk operations: 500ms

**Metrics**:
- Response time in milliseconds
- Threshold compliance
- Performance score

#### 7. WorkflowSequenceValidator
**What it validates**: Complete workflow sequences execute all steps successfully

**Example sequence** (Draft → Active):
1. Create lease (draft state)
2. Request approval
3. Approve lease
4. Send digital link
5. Mark as signed
6. Activate lease

**Metrics**:
- All steps completion
- Workflow execution success

## Running the Evaluation

### Prerequisites

1. **Install dependencies**:
```bash
pip install -r evaluation_requirements.txt
```

2. **Verify test data exists**:
```
evaluation_data/
├── test_queries.json
├── test_responses.json
└── evaluation_dataset.jsonl (auto-generated)
```

### Basic Usage

```bash
# Run evaluation with default configuration
python evaluation_framework.py
```

### Advanced Usage

```python
from evaluation_framework import (
    prepare_evaluation_dataset,
    run_evaluation,
    LeaseStateTransitionValidator
)

# Prepare dataset
dataset_file = prepare_evaluation_dataset(
    queries_file="evaluation_data/test_queries.json",
    responses_file="evaluation_data/test_responses.json",
    output_file="evaluation_data/evaluation_dataset.jsonl"
)

# Run evaluation
results = run_evaluation(
    dataset_file=dataset_file,
    output_dir="evaluation_results"
)
```

## Understanding Results

### Output Structure

```
evaluation_results/
└── evaluation_results.json
    ├── Row-level results
    │   ├── Individual evaluator scores per test case
    │   ├── Detailed validation reasons
    │   └── Performance metrics per operation
    │
    └── Aggregate metrics
        ├── Per-evaluator summary statistics
        ├── Overall validation rates (%)
        ├── Performance percentiles
        └── Pass/fail counts by category
```

### Sample Metrics

```
state_transition:
  - validation_passed: 100% (2/2 tests)
  - Average score: 1.0

approval_workflow:
  - validation_passed: 100% (3/3 tests)
  - Decision accuracy: 100%

api_response_accuracy:
  - Status code accuracy: 100%
  - Response format compliance: 100%
  - HTTP 201 responses: 100%
  - HTTP 422 validations: 100%

data_validation:
  - Field validation: 100% (3/3)
  - Error rejection: 100%

data_consistency:
  - Audit logs: 100% created
  - Serial uniqueness: Enforced
  - State alignment: 100%

response_time:
  - Within thresholds: 95%
  - Avg response time: 234ms
  - P95: 567ms

workflow_sequence:
  - Complete workflows: 100% (1/1)
  - All steps succeeded: 100%
```

## Test Scenarios Overview

| ID | Category | Scenario | Type | Expected Outcome |
|---|---|---|---|---|
| query_001 | Business Logic | Valid draft→approved | Transition | Success |
| query_002 | Business Logic | Invalid draft→active | Transition | Error |
| query_003 | Approval | Request approval | Workflow | Success |
| query_004 | Approval | Approve lease | Decision | Success |
| query_005 | Approval | Reject lease | Decision | Success |
| query_006 | API | Create with valid data | POST | 201 Created |
| query_007 | API | Missing required fields | POST | 422 Error |
| query_008 | API | Invalid state update | PATCH | 400 Error |
| query_009 | Validation | Validate required fields | Check | Success |
| query_010 | Validation | Negative rent | Check | Error |
| query_011 | Validation | Invalid date range | Check | Error |
| query_012 | Consistency | Audit log creation | Verify | Exists |
| query_013 | Consistency | Serial uniqueness | Check | Error (duplicate) |
| query_014 | Consistency | State alignment | Verify | Match |
| query_015 | Verification | Valid serial verify | GET | 200 Success |
| query_016 | Verification | Invalid serial verify | GET | 404 Not Found |
| query_017 | Performance | List with pagination | GET | <500ms |
| query_018 | Performance | Create lease | POST | <1000ms |
| query_019 | Workflow | Complete draft→active | Sequence | All success |
| query_020 | Calculation | Lease term calculation | Validate | Correct |

## Extending the Framework

### Adding Custom Evaluators

```python
class CustomMetricValidator:
    """Your custom evaluator description."""
    
    def __init__(self, config=None):
        self.config = config
    
    def __call__(self, *, query: Dict, response: Dict, **kwargs) -> Dict[str, Any]:
        """
        Evaluate custom metric.
        
        Args:
            query: Test query
            response: System response
            
        Returns:
            Dictionary with evaluation results and score
        """
        is_valid = True  # Your logic here
        
        return {
            'custom_metric_valid': is_valid,
            'validation_passed': is_valid,
            'score': 1.0 if is_valid else 0.0
        }
```

### Adding Test Scenarios

Add entries to `evaluation_data/test_queries.json`:

```json
{
  "id": "query_021",
  "category": "custom_category",
  "scenario": "Your test scenario",
  "test_type": "your_test_type",
  "input": {
    "key": "value"
  },
  "expected_outcome": "success"
}
```

Then add corresponding response to `test_responses.json`:

```json
{
  "id": "response_021",
  "query_id": "query_021",
  "scenario": "Your test scenario",
  "status": "success",
  "response": {
    "success": true,
    "data": {}
  },
  "response_time_ms": 200
}
```

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Lease System Evaluation

on: [push, pull_request]

jobs:
  evaluate:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Set up Python
        uses: actions/setup-python@v2
        with:
          python-version: '3.10'
      
      - name: Install dependencies
        run: pip install -r evaluation_requirements.txt
      
      - name: Run evaluation
        run: python evaluation_framework.py
      
      - name: Upload results
        uses: actions/upload-artifact@v2
        with:
          name: evaluation-results
          path: evaluation_results/
```

## Troubleshooting

### Common Issues

**Issue**: `ModuleNotFoundError: No module named 'azure.ai.evaluation'`
**Solution**: Install dependencies
```bash
pip install -r evaluation_requirements.txt
```

**Issue**: `FileNotFoundError: evaluation_data/test_queries.json`
**Solution**: Ensure test data files exist in the correct location
```bash
ls evaluation_data/
```

**Issue**: Evaluation runs but produces low scores
**Solution**: Review evaluation results and update test responses if system behavior changed
```bash
cat evaluation_results/evaluation_results.json
```

## Performance Benchmarks

Expected performance baselines:

| Operation | Target | P95 | P99 |
|---|---|---|---|
| Create lease | <500ms | <800ms | <1200ms |
| List leases | <300ms | <500ms | <800ms |
| Transition state | <200ms | <350ms | <600ms |
| Request approval | <300ms | <500ms | <800ms |
| Verify lease | <300ms | <500ms | <800ms |

## Support & Maintenance

- Review evaluation results regularly
- Update test scenarios when adding new features
- Monitor performance trends over time
- Adjust thresholds based on production baselines
- Add regression tests for bug fixes

---

**Last Updated**: January 18, 2026
**Framework Version**: 1.0
**Status**: Production Ready ✅
