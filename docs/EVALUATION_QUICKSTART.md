# Evaluation Framework - Quick Start Guide

## 🚀 Quick Setup (5 minutes)

### Step 1: Install Dependencies
```bash
cd c:\Users\kiman\Projects\chabrin-lease-system
pip install -r evaluation_requirements.txt
```

### Step 2: Run Evaluation
```bash
python evaluation_framework.py
```

### Step 3: View Results
```bash
# Results will be in:
# evaluation_results/evaluation_results.json
```

---

## 📊 What Gets Evaluated

### ✅ 7 Evaluation Metrics

1. **State Transition** - Valid lease workflow transitions
2. **Approval Workflow** - Landlord approval/rejection logic
3. **API Responses** - Correct status codes and formats
4. **Data Validation** - Required fields and constraints
5. **Data Consistency** - Audit logs and uniqueness
6. **Response Times** - Performance thresholds
7. **Workflow Sequences** - End-to-end lease lifecycle

### ✅ 20 Test Scenarios

- 3 Business logic tests
- 3 Approval workflow tests
- 3 API validation tests
- 3 Data validation tests
- 3 Data consistency tests
- 2 Verification tests
- 2 Performance tests
- 1 Complete workflow test

---

## 📂 File Structure

```
chabrin-lease-system/
├── evaluation_framework.py              # Main evaluation engine
├── evaluation_requirements.txt          # Dependencies
├── EVALUATION_FRAMEWORK.md             # Full documentation
│
├── evaluation_data/
│   ├── test_queries.json               # 20 test scenarios
│   ├── test_responses.json             # Expected responses
│   └── evaluation_dataset.jsonl        # Auto-generated dataset
│
└── evaluation_results/
    └── evaluation_results.json         # Results (generated on run)
```

---

## 🎯 Key Features

### Comprehensive Testing
- **Business Logic**: State transitions, approvals, workflows
- **API Validation**: Status codes, response formats, errors
- **Data Quality**: Validation, consistency, audit trails
- **Performance**: Response times, efficiency metrics

### Easy to Extend
- Add custom evaluators in minutes
- Define new test scenarios as JSON
- Automatic result aggregation
- Detailed per-test feedback

### Production Ready
- Azure AI Evaluation SDK integration
- JSONL dataset support
- Configurable thresholds
- CI/CD friendly

---

## 🔍 Reading Results

### Success Indicators
```
✅ state_transition: validation_passed = 1.0 (100%)
✅ approval_workflow: validation_passed = 1.0 (100%)
✅ api_response_accuracy: validation_passed = 1.0 (100%)
```

### Problem Indicators
```
❌ data_validation: validation_passed = 0.0 (0%)
   - Need to check: validation_works_correctly
   - Review test scenario and expected outcome
```

### Performance Metrics
```
📊 response_time: 
   - response_time_ms: 234
   - expected_max_ms: 500
   - within_threshold: true ✅
```

---

## 🛠️ Common Tasks

### Run Full Evaluation
```bash
python evaluation_framework.py
```

### Check Single Evaluator
```python
from evaluation_framework import LeaseStateTransitionValidator

validator = LeaseStateTransitionValidator()
result = validator(query={...}, response={...})
print(result)
```

### Add New Test Scenario
1. Add entry to `evaluation_data/test_queries.json`
2. Add corresponding response to `evaluation_data/test_responses.json`
3. Run: `python evaluation_framework.py`

### View Results in Python
```python
import json

with open('evaluation_results/evaluation_results.json', 'r') as f:
    results = json.load(f)
    
# Access aggregate metrics
print(results['aggregate_metrics'])

# Access row-level results
for row in results['rows']:
    print(f"{row['query_id']}: {row['scores']}")
```

---

## 📈 Expected Results

When everything works correctly:

```
🚀 Starting Comprehensive Lease System Evaluation
==================================================

📊 Running evaluators...
   - State Transition Validator ✅
   - Approval Workflow Validator ✅
   - API Response Accuracy Validator ✅
   - Data Validation Validator ✅
   - Data Consistency Validator ✅
   - Response Time Validator ✅
   - Workflow Sequence Validator ✅

✅ Evaluation Completed Successfully!
==================================================

📈 Aggregate Metrics:
   state_transition: 100.00%
   approval_workflow: 100.00%
   api_response_accuracy: 100.00%
   data_validation: 100.00%
   data_consistency: 100.00%
   response_time: 95.00%
   workflow_sequence: 100.00%

💾 Results saved to: evaluation_results/evaluation_results.json
```

---

## 🆘 Troubleshooting

### Error: `ModuleNotFoundError: No module named 'azure.ai.evaluation'`
**Solution**: Install dependencies
```bash
pip install -r evaluation_requirements.txt
```

### Error: `FileNotFoundError: evaluation_data/test_queries.json`
**Solution**: Verify files exist
```bash
ls evaluation_data/
# Should show: test_queries.json, test_responses.json
```

### Low Evaluation Scores
**Solution**: Check if system behavior changed
1. Review results: `cat evaluation_results/evaluation_results.json`
2. Update test responses if needed
3. Re-run evaluation

### Tests Fail in Specific Evaluator
**Solution**: Debug the specific evaluator
```python
from evaluation_framework import APIResponseAccuracyValidator

validator = APIResponseAccuracyValidator()
# Test with specific query/response
result = validator(query={...}, response={...})
print(json.dumps(result, indent=2))
```

---

## 📚 Next Steps

1. ✅ Run the framework: `python evaluation_framework.py`
2. 📊 Review the results in `evaluation_results/evaluation_results.json`
3. 📖 Read full docs: `EVALUATION_FRAMEWORK.md`
4. 🔧 Customize test scenarios as needed
5. 🔄 Integrate into CI/CD pipeline

---

## 💡 Tips

- **Regular Testing**: Run evaluation on every deployment
- **Trend Monitoring**: Track metrics over time for regressions
- **Threshold Tuning**: Adjust performance thresholds based on baselines
- **Scenario Expansion**: Add tests when adding new features
- **Team Awareness**: Share results with development team

---

**Need Help?** Check `EVALUATION_FRAMEWORK.md` for comprehensive documentation.

**Status**: ✅ Ready to use!
