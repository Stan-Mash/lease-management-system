#!/usr/bin/env python3
"""
Comprehensive Evaluation Framework for Chabrin Lease Management System

This module implements custom evaluators for:
1. Lease State Transition Validation
2. Approval Workflow Validation
3. API Response Accuracy
4. Data Validation
5. Lease Data Consistency
6. Workflow State Consistency
7. Response Time Performance
8. Database Query Efficiency
"""

import json
import os
from typing import Any, Dict, Optional
from azure.ai.evaluation import evaluate


# ============================================================================
# Custom Code-Based Evaluators for Business Logic Validation
# ============================================================================

class LeaseStateTransitionValidator:
    """
    Validates that lease state transitions follow business rules.

    Valid transitions are:
    - draft → [approved, cancelled]
    - approved → [printed, sent_digital, cancelled]
    - And various other defined transitions
    """

    def __init__(self):
        self.valid_transitions = {
            'draft': ['approved', 'cancelled'],
            'approved': ['printed', 'sent_digital', 'cancelled'],
            'printed': ['checked_out', 'cancelled'],
            'checked_out': ['pending_tenant_signature', 'returned_unsigned'],
            'sent_digital': ['pending_otp', 'cancelled'],
            'pending_tenant_signature': ['tenant_signed', 'returned_unsigned'],
            'tenant_signed': ['with_lawyer', 'pending_upload', 'pending_deposit'],
            'with_lawyer': ['pending_upload', 'pending_deposit'],
            'pending_upload': ['pending_deposit'],
            'pending_deposit': ['active'],
            'active': ['renewal_offered', 'expired', 'terminated'],
            'expired': ['archived'],
            'terminated': ['archived'],
            'cancelled': ['archived'],
        }

    def __call__(self, *, query: Dict, response: Dict, **kwargs) -> Dict[str, Any]:
        """
        Evaluate if a state transition is valid.

        Args:
            query: Test query containing current_state and target_state
            response: Response from the system

        Returns:
            Dictionary with validation result
        """
        try:
            current_state = query.get('input', {}).get('current_state')
            target_state = query.get('input', {}).get('target_state')
            expected_outcome = query.get('expected_outcome')
            response_status = response.get('status')

            # Check if transition is valid according to business rules
            is_valid_transition = (
                current_state in self.valid_transitions and
                target_state in self.valid_transitions.get(current_state, [])
            )

            # Check if response matches expected outcome
            expected_success = expected_outcome == 'success'
            response_success = response_status == 'success'

            is_correct = is_valid_transition == expected_success
            if is_correct:
                is_correct = response_success == expected_success

            return {
                'state_transition_valid': is_valid_transition,
                'response_matches_expectation': response_success == expected_success,
                'validation_passed': is_correct,
                'score': 1.0 if is_correct else 0.0
            }
        except Exception as e:
            return {
                'error': str(e),
                'validation_passed': False,
                'score': 0.0
            }


class ApprovalWorkflowValidator:
    """
    Validates landlord approval, rejection, and resubmission workflow logic.
    """

    def __call__(self, *, query: Dict, response: Dict, **kwargs) -> Dict[str, Any]:
        """
        Evaluate if approval workflow is executed correctly.
        """
        try:
            test_type = query.get('test_type')
            expected_outcome = query.get('expected_outcome')
            response_status = response.get('status')
            response_data = response.get('response', {}).get('data', {})

            # For approval requests, check if state transitions to pending
            if test_type == 'approval_request':
                workflow_state = response_data.get('workflow_state')
                is_valid = (
                    workflow_state == 'pending_landlord_approval' and
                    response_status == 'success'
                )

            # For approval decisions, check decision is recorded
            elif test_type == 'approval_decision':
                decision = response_data.get('decision')
                approved_action = query.get('input', {}).get('approval_action')
                is_valid = (
                    decision == approved_action and
                    response_status == 'success'
                )

            else:
                is_valid = response_status == expected_outcome

            return {
                'approval_workflow_valid': is_valid,
                'response_matches_expectation': response_status == expected_outcome,
                'validation_passed': is_valid,
                'score': 1.0 if is_valid else 0.0
            }
        except Exception as e:
            return {
                'error': str(e),
                'validation_passed': False,
                'score': 0.0
            }


class APIResponseAccuracyValidator:
    """
    Validates API responses have correct status codes and formats.
    """

    def __call__(self, *, query: Dict, response: Dict, **kwargs) -> Dict[str, Any]:
        """
        Evaluate if API response is accurate and properly formatted.
        """
        try:
            expected_status = query.get('expected_status')
            actual_status = response.get('http_status')
            expected_outcome = query.get('expected_outcome')
            response_status = response.get('status')
            response_data = response.get('response', {})

            # Check status code matches
            status_correct = actual_status == expected_status if expected_status else True

            # Check response has expected structure
            has_response = bool(response_data)

            # Check outcome matches expectation
            outcome_correct = response_status == expected_outcome

            is_valid = status_correct and outcome_correct and has_response

            return {
                'status_code_correct': status_correct,
                'has_response_data': has_response,
                'outcome_matches_expectation': outcome_correct,
                'validation_passed': is_valid,
                'score': 1.0 if is_valid else 0.0
            }
        except Exception as e:
            return {
                'error': str(e),
                'validation_passed': False,
                'score': 0.0
            }


class DataValidationValidator:
    """
    Validates that required fields are present and invalid data is rejected.
    """

    def __call__(self, *, query: Dict, response: Dict, **kwargs) -> Dict[str, Any]:
        """
        Evaluate if data validation works correctly.
        """
        try:
            test_type = query.get('test_type')
            expected_outcome = query.get('expected_outcome')
            response_status = response.get('status')
            response_data = response.get('response', {})

            if test_type == 'field_validation':
                # Check if response matches expected outcome
                is_valid = response_status == expected_outcome

                # For missing field tests, check error is returned
                if expected_outcome == 'error':
                    has_error = 'error' in response_data or 'errors' in response_data
                    is_valid = is_valid and has_error
            else:
                is_valid = response_status == expected_outcome

            return {
                'validation_works_correctly': is_valid,
                'response_matches_expectation': response_status == expected_outcome,
                'validation_passed': is_valid,
                'score': 1.0 if is_valid else 0.0
            }
        except Exception as e:
            return {
                'error': str(e),
                'validation_passed': False,
                'score': 0.0
            }


class LeaseDataConsistencyValidator:
    """
    Validates audit logs, serial number uniqueness, and record consistency.
    """

    def __call__(self, *, query: Dict, response: Dict, **kwargs) -> Dict[str, Any]:
        """
        Evaluate if data consistency is maintained.
        """
        try:
            test_type = query.get('test_type')
            response_status = response.get('status')
            response_data = response.get('response', {}).get('data', {})

            if test_type == 'audit_logging':
                # Check audit log exists and has correct data
                audit_log_exists = response_data.get('audit_log_exists', False)
                audit_log = response_data.get('audit_log', {})

                is_valid = (
                    audit_log_exists and
                    audit_log.get('action') == 'state_transition' and
                    'created_at' in audit_log
                )

            elif test_type == 'uniqueness_check':
                # For duplicate tests, should return error
                is_valid = response_status == 'error'

            elif test_type == 'record_consistency':
                # Check states match
                states_match = response_data.get('states_match', False)
                is_valid = states_match and response_status == 'success'

            else:
                is_valid = response_status == 'success'

            return {
                'data_consistency_valid': is_valid,
                'validation_passed': is_valid,
                'score': 1.0 if is_valid else 0.0
            }
        except Exception as e:
            return {
                'error': str(e),
                'validation_passed': False,
                'score': 0.0
            }


class ResponseTimeValidator:
    """
    Validates API response times meet performance thresholds.
    """

    def __init__(self, default_max_ms: int = 1000):
        self.default_max_ms = default_max_ms

    def __call__(self, *, query: Dict, response: Dict, **kwargs) -> Dict[str, Any]:
        """
        Evaluate if response time is within acceptable thresholds.
        """
        try:
            response_time_ms = response.get('response_time_ms', 0)
            expected_max_ms = query.get('expected_max_response_time_ms', self.default_max_ms)

            within_threshold = response_time_ms <= expected_max_ms

            return {
                'response_time_ms': response_time_ms,
                'expected_max_ms': expected_max_ms,
                'within_threshold': within_threshold,
                'score': 1.0 if within_threshold else max(0, 1.0 - (response_time_ms / (expected_max_ms * 2)))
            }
        except Exception as e:
            return {
                'error': str(e),
                'within_threshold': False,
                'score': 0.0
            }


class WorkflowSequenceValidator:
    """
    Validates complete workflow sequences execute all steps successfully.
    """

    def __call__(self, *, query: Dict, response: Dict, **kwargs) -> Dict[str, Any]:
        """
        Evaluate if complete workflow sequence executes correctly.
        """
        try:
            test_type = query.get('test_type')
            expected_outcome = query.get('expected_outcome')
            response_status = response.get('status')

            if test_type == 'workflow_sequence':
                response_data = response.get('response', {}).get('data', {})
                workflow_steps = response_data.get('workflow_steps', [])

                # Check all steps succeeded
                all_steps_succeeded = all(
                    step.get('status') == 'success' for step in workflow_steps
                )

                # Check total workflow succeeded
                workflow_succeeded = response_status == 'success'

                is_valid = all_steps_succeeded and workflow_succeeded
            else:
                is_valid = response_status == expected_outcome

            return {
                'workflow_sequence_valid': is_valid,
                'validation_passed': is_valid,
                'score': 1.0 if is_valid else 0.0
            }
        except Exception as e:
            return {
                'error': str(e),
                'validation_passed': False,
                'score': 0.0
            }


# ============================================================================
# Evaluation Execution
# ============================================================================

def prepare_evaluation_dataset(queries_file: str, responses_file: str, output_file: str) -> str:
    """
    Prepare evaluation dataset in JSONL format by merging queries and responses.

    Args:
        queries_file: Path to test_queries.json
        responses_file: Path to test_responses.json
        output_file: Path to output JSONL file

    Returns:
        Path to prepared JSONL file
    """
    # Load queries and responses
    with open(queries_file, 'r') as f:
        queries = json.load(f)

    with open(responses_file, 'r') as f:
        responses = json.load(f)

    # Create mapping of query_id to response
    response_map = {r['query_id']: r for r in responses}

    # Prepare JSONL data
    jsonl_data = []
    for query in queries:
        query_id = query['id']
        response = response_map.get(query_id, {})

        # Combine query and response
        data_row = {
            'query_id': query_id,
            'scenario': query.get('scenario'),
            'category': query.get('category'),
            'query': query,
            'response': response
        }
        jsonl_data.append(data_row)

    # Write JSONL file
    with open(output_file, 'w') as f:
        for row in jsonl_data:
            f.write(json.dumps(row) + '\n')

    print(f"✅ Prepared evaluation dataset: {output_file}")
    print(f"   Total rows: {len(jsonl_data)}")

    return output_file


def run_evaluation(dataset_file: str, output_dir: str = "evaluation_results"):
    """
    Execute comprehensive evaluation of the lease management system.

    Args:
        dataset_file: Path to JSONL evaluation dataset
        output_dir: Directory to save evaluation results
    """

    # Create output directory if it doesn't exist
    os.makedirs(output_dir, exist_ok=True)

    print("\n" + "="*70)
    print("🚀 Starting Comprehensive Lease System Evaluation")
    print("="*70 + "\n")

    # Initialize evaluators
    evaluators = {
        'state_transition': LeaseStateTransitionValidator(),
        'approval_workflow': ApprovalWorkflowValidator(),
        'api_response_accuracy': APIResponseAccuracyValidator(),
        'data_validation': DataValidationValidator(),
        'data_consistency': LeaseDataConsistencyValidator(),
        'response_time': ResponseTimeValidator(),
        'workflow_sequence': WorkflowSequenceValidator(),
    }

    # Define column mappings for evaluators
    evaluator_config = {
        'state_transition': {
            'column_mapping': {
                'query': '${data.query}',
                'response': '${data.response}'
            }
        },
        'approval_workflow': {
            'column_mapping': {
                'query': '${data.query}',
                'response': '${data.response}'
            }
        },
        'api_response_accuracy': {
            'column_mapping': {
                'query': '${data.query}',
                'response': '${data.response}'
            }
        },
        'data_validation': {
            'column_mapping': {
                'query': '${data.query}',
                'response': '${data.response}'
            }
        },
        'data_consistency': {
            'column_mapping': {
                'query': '${data.query}',
                'response': '${data.response}'
            }
        },
        'response_time': {
            'column_mapping': {
                'query': '${data.query}',
                'response': '${data.response}'
            }
        },
        'workflow_sequence': {
            'column_mapping': {
                'query': '${data.query}',
                'response': '${data.response}'
            }
        },
    }

    # Run evaluation
    print("📊 Running evaluators...")
    print(f"   - State Transition Validator")
    print(f"   - Approval Workflow Validator")
    print(f"   - API Response Accuracy Validator")
    print(f"   - Data Validation Validator")
    print(f"   - Data Consistency Validator")
    print(f"   - Response Time Validator")
    print(f"   - Workflow Sequence Validator")
    print()

    try:
        result = evaluate(
            data=dataset_file,
            evaluators=evaluators,
            evaluator_config=evaluator_config,
            output_path=os.path.join(output_dir, "evaluation_results.json")
        )

        print("\n" + "="*70)
        print("✅ Evaluation Completed Successfully!")
        print("="*70 + "\n")

        # Display aggregate metrics
        if hasattr(result, 'aggregate_metrics'):
            print("📈 Aggregate Metrics:")
            print("-" * 70)
            for metric_name, metric_value in result.aggregate_metrics.items():
                if isinstance(metric_value, float):
                    print(f"   {metric_name}: {metric_value:.2%}")
                else:
                    print(f"   {metric_name}: {metric_value}")

        print(f"\n💾 Results saved to: {output_dir}/evaluation_results.json")
        print(f"📊 View detailed results for further analysis\n")

        return result

    except Exception as e:
        print(f"\n❌ Evaluation failed with error: {str(e)}\n")
        raise


def main():
    """Main entry point for evaluation."""

    # Setup file paths
    base_dir = os.path.dirname(os.path.abspath(__file__))
    eval_data_dir = os.path.join(os.path.dirname(base_dir), "evaluation_data")

    queries_file = os.path.join(eval_data_dir, "test_queries.json")
    responses_file = os.path.join(eval_data_dir, "test_responses.json")
    dataset_file = os.path.join(eval_data_dir, "evaluation_dataset.jsonl")

    # Validate input files exist
    if not os.path.exists(queries_file):
        print(f"❌ Queries file not found: {queries_file}")
        return

    if not os.path.exists(responses_file):
        print(f"❌ Responses file not found: {responses_file}")
        return

    # Prepare evaluation dataset
    print("\n📝 Preparing evaluation dataset...\n")
    prepare_evaluation_dataset(queries_file, responses_file, dataset_file)

    # Run evaluation
    output_dir = os.path.join(base_dir, "evaluation_results")
    run_evaluation(dataset_file, output_dir)


if __name__ == "__main__":
    main()
