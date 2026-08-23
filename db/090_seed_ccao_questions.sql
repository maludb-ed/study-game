-- Seed: CCAO-F (Associate) question bank, distributed per the official blueprint:
-- Output Evaluation 15, Workflow Integration 11, Governance 10, Prompting 10,
-- Product/Model Selection 8, Configuration/KM 8, Troubleshooting 7  (= 69).
-- Original study questions written from public Anthropic documentation — never
-- copied from real exam items. All active, source 'claude-generated 2026-08'.

create or replace function seed_q(
    p_exam text, p_domain text, p_diff text, p_stem text,
    p_o1 text, p_o2 text, p_o3 text, p_o4 text, p_correct int, p_expl text
) returns void language plpgsql as $fn$
declare
    v_exam_id bigint;
    v_domain_id bigint;
    v_question_id bigint;
begin
    select e.id into v_exam_id from exams e where e.code = p_exam;
    select d.id into v_domain_id from domains d where d.exam_id = v_exam_id and d.name = p_domain;
    if v_domain_id is null then
        raise exception 'Unknown domain % for exam %', p_domain, p_exam;
    end if;
    insert into questions (exam_id, domain_id, stem, explanation, difficulty, status, source)
    values (v_exam_id, v_domain_id, p_stem, p_expl, p_diff, 'active', 'claude-generated 2026-08')
    returning id into v_question_id;
    insert into question_options (question_id, option_text, is_correct, display_order) values
        (v_question_id, p_o1, p_correct = 1, 1),
        (v_question_id, p_o2, p_correct = 2, 2),
        (v_question_id, p_o3, p_correct = 3, 3),
        (v_question_id, p_o4, p_correct = 4, 4);
end;
$fn$;

-- ============ Output Evaluation and Validation (15) ============

select seed_q('CCAO-F', 'Output Evaluation and Validation', 'easy',
$q$A marketing manager asks Claude for statistics about their industry and gets specific-sounding numbers with no sources. What should they do before using them?$q$,
$q$Use them — Claude only states facts it has verified$q$,
$q$Verify the numbers against authoritative sources before publishing$q$,
$q$Ask Claude to repeat the numbers to confirm consistency$q$,
$q$Round the numbers so accuracy matters less$q$, 2,
$q$Language models can produce plausible but fabricated figures (hallucinations). Repeating a claim consistently is not evidence it is true — external verification against authoritative sources is required before publication.$q$);

select seed_q('CCAO-F', 'Output Evaluation and Validation', 'medium',
$q$Which review approach best fits a workflow where Claude drafts responses to routine customer emails?$q$,
$q$Send drafts automatically; sampling a few per month is enough$q$,
$q$A human reviews each draft before sending, with extra scrutiny on refunds or commitments$q$,
$q$Only spell-check the drafts, since tone is subjective$q$,
$q$Have a second Claude conversation approve each draft automatically$q$, 2,
$q$Human review before send is the standard control for outbound communication. Messages that commit the company (refunds, promises) carry the highest risk and deserve the most scrutiny. Model-approves-model without a human still leaves no accountable reviewer.$q$);

select seed_q('CCAO-F', 'Output Evaluation and Validation', 'medium',
$q$Claude summarizes a 40-page contract for an operations lead. What is the most reliable quick check that the summary is trustworthy?$q$,
$q$Check the summary's grammar and formatting$q$,
$q$Ask Claude how confident it is in the summary$q$,
$q$Spot-check key claims in the summary against the specific sections of the source document$q$,
$q$Compare its length to summaries of similar contracts$q$, 3,
$q$Tracing claims back to the source document tests faithfulness directly. Self-reported confidence and surface polish do not indicate accuracy.$q$);

select seed_q('CCAO-F', 'Output Evaluation and Validation', 'medium',
$q$A team uses Claude to extract vendor names and totals from invoices. Which validation gives the best effort-to-assurance tradeoff at volume?$q$,
$q$Fully re-key every invoice by hand to compare$q$,
$q$Automated checks (totals reconcile, vendors match the vendor list) plus human review of flagged and sampled items$q$,
$q$Trust extractions that look complete$q$,
$q$Run every invoice through Claude twice and keep the first answer$q$, 2,
$q$Layered validation — cheap automated consistency checks on everything, human attention on exceptions and a random sample — scales far better than full manual re-keying and is far safer than trusting unchecked output.$q$);

select seed_q('CCAO-F', 'Output Evaluation and Validation', 'hard',
$q$Two evaluators rate Claude's draft answers as "good" or "bad" and disagree often. What should the team fix first to make evaluation useful?$q$,
$q$Add a third evaluator to break ties$q$,
$q$Write concrete rating criteria with examples, so evaluators apply the same standard$q$,
$q$Let Claude rate its own answers instead$q$,
$q$Average the two ratings into a score$q$, 2,
$q$High disagreement signals the rubric is ambiguous. Defining concrete criteria with worked examples fixes the measurement instrument; adding voters or averaging just hides the inconsistency.$q$);

select seed_q('CCAO-F', 'Output Evaluation and Validation', 'medium',
$q$When Claude cites a real-sounding article title and author to support a claim, the safest assumption is:$q$,
$q$The citation is real because it includes specific details$q$,
$q$The citation may be fabricated and must be checked before relying on it$q$,
$q$Citations are only unreliable for pre-2020 material$q$,
$q$The claim is true even if the citation is wrong$q$, 2,
$q$Models can fabricate realistic-looking citations. Specificity is not evidence of existence — check that the source exists and actually supports the claim.$q$);

select seed_q('CCAO-F', 'Output Evaluation and Validation', 'easy',
$q$What is a "hallucination" in the context of using Claude at work?$q$,
$q$Any answer longer than requested$q$,
$q$Confident output that is factually wrong or invented$q$,
$q$An answer that refuses the request$q$,
$q$Output that repeats the prompt$q$, 2,
$q$Hallucination means confidently stated content that is false or invented — the key risk being that it reads as authoritative.$q$);

select seed_q('CCAO-F', 'Output Evaluation and Validation', 'medium',
$q$A finance analyst wants Claude to be transparent about uncertainty in a market analysis. Which instruction helps most?$q$,
$q$"Sound as confident as possible."$q$,
$q$"If you are unsure about a figure or claim, say so explicitly and explain what would need checking."$q$,
$q$"Do not include any numbers."$q$,
$q$"Keep the analysis under 100 words."$q$, 2,
$q$Explicitly inviting uncertainty markers produces output that separates solid claims from ones needing verification — exactly what a reviewer needs.$q$);

select seed_q('CCAO-F', 'Output Evaluation and Validation', 'medium',
$q$Before rolling a Claude-drafted report template out to the whole team, the best low-cost evaluation is to:$q$,
$q$Pilot it on a representative set of past cases and compare against the reports humans actually produced$q$,
$q$Ask Claude whether the template is good$q$,
$q$Roll it out and collect complaints$q$,
$q$Test it once on the simplest case$q$, 1,
$q$Piloting against a representative sample with known-good human outputs reveals gaps before they spread. Single easy cases and after-the-fact complaints both find problems too late.$q$);

select seed_q('CCAO-F', 'Output Evaluation and Validation', 'medium',
$q$Claude produces a beautifully formatted, decisive-sounding analysis. Why is polish alone a poor signal of quality?$q$,
$q$Formatting slows readers down$q$,
$q$Fluent, confident prose is generated regardless of whether the underlying claims are right$q$,
$q$Well-formatted answers are usually shorter$q$,
$q$Polish means the model spent less effort on facts$q$, 2,
$q$Fluency and confidence are properties of generation, not correctness. Substance must be evaluated separately from style.$q$);

select seed_q('CCAO-F', 'Output Evaluation and Validation', 'medium',
$q$For a recurring weekly task, what turns ad-hoc spot checks into a real quality process?$q$,
$q$Checking only when something feels off$q$,
$q$A defined sample rate, clear pass criteria, and tracking results over time$q$,
$q$Replacing checks with a better prompt$q$,
$q$Rotating who checks so no one gets bored$q$, 2,
$q$A quality process needs defined sampling, explicit criteria, and trend tracking so drift is caught systematically rather than by luck.$q$);

select seed_q('CCAO-F', 'Output Evaluation and Validation', 'easy',
$q$Claude translates a customer announcement into a language nobody on the team speaks. The right next step is:$q$,
$q$Publish it — translation is deterministic$q$,
$q$Have a fluent speaker review it before publication$q$,
$q$Translate it back with Claude and publish if it round-trips$q$,
$q$Publish with a disclaimer that it was machine-translated$q$, 2,
$q$Outbound content in a language the team cannot read needs a qualified human reviewer. Round-tripping can hide errors, and a disclaimer does not prevent the damage of a bad announcement.$q$);

select seed_q('CCAO-F', 'Output Evaluation and Validation', 'hard',
$q$A team compares two prompts by eyeballing one output from each. Why is this comparison weak, and what fixes it?$q$,
$q$Weak because outputs vary between runs; fix by comparing multiple outputs on the same representative input set against defined criteria$q$,
$q$Weak because one output was longer; fix by trimming both$q$,
$q$Weak because the prompts differ; fix by making them identical$q$,
$q$It is not weak — one sample per prompt is standard$q$, 1,
$q$Model output varies run to run, so single samples confound prompt quality with randomness. Comparing several outputs over the same representative inputs with defined criteria isolates the prompt's actual effect.$q$);

select seed_q('CCAO-F', 'Output Evaluation and Validation', 'medium',
$q$Which task most demands verbatim human verification of Claude's output rather than sampling?$q$,
$q$Brainstorming campaign names$q$,
$q$Summarizing internal meeting notes$q$,
$q$Drafting dosage or safety-critical instructions$q$,
$q$Suggesting subject lines for a newsletter$q$, 3,
$q$Verification effort scales with consequence of error. Safety-critical content is checked in full, every time; creative and internal low-stakes tasks tolerate sampling.$q$);

select seed_q('CCAO-F', 'Output Evaluation and Validation', 'medium',
$q$After a month in production, a Claude-assisted triage workflow starts mislabeling a new type of request. What practice would have caught this early?$q$,
$q$A one-time evaluation before launch$q$,
$q$Ongoing monitoring: sampled review of live outputs with results tracked over time$q$,
$q$A longer system prompt at launch$q$,
$q$Restricting the workflow to fewer users$q$, 2,
$q$Input mix changes over time, so quality must be monitored continuously. A pre-launch evaluation cannot see categories that appear later.$q$);

-- ============ Workflow Integration and Solution Design (11) ============

select seed_q('CCAO-F', 'Workflow Integration and Solution Design', 'medium',
$q$Which task is the strongest first candidate for Claude assistance in a busy operations team?$q$,
$q$Final sign-off on vendor contracts$q$,
$q$Drafting the recurring weekly status summary from project notes, reviewed by the owner$q$,
$q$Deciding annual budget allocations$q$,
$q$Approving employee expense exceptions$q$, 2,
$q$Good first candidates are frequent, time-consuming, text-heavy tasks where a human retains review. Judgment-heavy approvals and sign-offs should stay with people.$q$);

select seed_q('CCAO-F', 'Workflow Integration and Solution Design', 'medium',
$q$When designing a Claude-assisted workflow, "human in the loop" means:$q$,
$q$A person wrote the original prompt$q$,
$q$A person reviews or approves the model's work at defined points before it takes effect$q$,
$q$A person is available if the system crashes$q$,
$q$The model asks clarifying questions$q$, 2,
$q$Human-in-the-loop places a person at defined checkpoints with authority to catch and correct the model's work before it has consequences.$q$);

select seed_q('CCAO-F', 'Workflow Integration and Solution Design', 'hard',
$q$A team wants Claude to fully automate responses to customer complaints. What design consideration argues for keeping a human approval step initially?$q$,
$q$Automation is always slower than manual work$q$,
$q$Complaint responses carry reputational and commitment risk, and error patterns are unknown until observed in production$q$,
$q$Customers can tell when a machine writes text$q$,
$q$Approval steps reduce licensing costs$q$, 2,
$q$New workflows have unknown failure modes, and complaints are high-stakes outbound communication. Starting with human approval lets the team learn the error profile before granting autonomy.$q$);

select seed_q('CCAO-F', 'Workflow Integration and Solution Design', 'medium',
$q$What is the best way to decide whether a Claude-assisted workflow actually helped after a quarter?$q$,
$q$Ask the team if it feels faster$q$,
$q$Compare measurable baselines set before rollout — time per task, error rate, throughput — against current numbers$q$,
$q$Count how many prompts were written$q$,
$q$Check whether anyone turned it off$q$, 2,
$q$Value is demonstrated against a pre-rollout baseline on agreed metrics. Feelings and usage counts do not show whether outcomes improved.$q$);

select seed_q('CCAO-F', 'Workflow Integration and Solution Design', 'medium',
$q$A recruiter screens hundreds of applications weekly. The most appropriate role for Claude is:$q$,
$q$Making final reject decisions autonomously$q$,
$q$Summarizing each application against the role requirements for the recruiter's decision$q$,
$q$Ranking candidates by predicted culture fit$q$,
$q$Emailing rejections without review$q$, 2,
$q$Employment decisions are consequential and regulated; the defensible design has Claude accelerate reading (summaries against stated criteria) while humans make and own every decision.$q$);

select seed_q('CCAO-F', 'Workflow Integration and Solution Design', 'easy',
$q$Which describes a "pilot" phase done well for a new Claude workflow?$q$,
$q$Company-wide launch with a feedback form$q$,
$q$A small group uses it on real work for a set period, with success criteria agreed in advance$q$,
$q$The IT team demos it once$q$,
$q$Running it on invented sample data only$q$, 2,
$q$A pilot is a bounded real-work trial with pre-agreed success criteria — real inputs, limited blast radius, and a defined decision point.$q$);

select seed_q('CCAO-F', 'Workflow Integration and Solution Design', 'medium',
$q$A workflow needs Claude to read the latest product FAQ every time it answers. The best design is to:$q$,
$q$Paste the FAQ into each chat manually$q$,
$q$Keep the FAQ as maintained project knowledge or a connected source the workflow always uses$q$,
$q$Trust the model's general knowledge about the product$q$,
$q$Retrain staff to answer without Claude$q$, 2,
$q$Recurring workflows should draw on maintained, versioned knowledge (project knowledge or a connected source) so answers stay current without per-chat pasting — and general model knowledge will not know your product's specifics.$q$);

select seed_q('CCAO-F', 'Workflow Integration and Solution Design', 'medium',
$q$Which failure mode does a clear escalation path protect against in a Claude-assisted support workflow?$q$,
$q$The model answering too quickly$q$,
$q$Cases the model handles poorly getting stuck instead of reaching a human with authority to resolve them$q$,
$q$Customers preferring email over chat$q$,
$q$Agents forgetting their passwords$q$, 2,
$q$Escalation paths ensure the workflow fails safely: anything outside the model's competence moves to a human quickly rather than looping or being answered badly.$q$);

select seed_q('CCAO-F', 'Workflow Integration and Solution Design', 'hard',
$q$An analyst builds a personal Claude workflow that saves hours weekly. To turn it into a team solution, the most important next step is:$q$,
$q$Keep it personal to avoid change management$q$,
$q$Document the prompts, inputs, and review steps, then standardize them so results do not depend on one person$q$,
$q$Ask everyone to invent their own version$q$,
$q$Automate it fully so no one needs to understand it$q$, 2,
$q$Team adoption requires the workflow to be explicit and repeatable: documented prompts, defined inputs, and review steps that produce consistent results across users.$q$);

select seed_q('CCAO-F', 'Workflow Integration and Solution Design', 'easy',
$q$Which task should NOT be delegated to Claude even with review, under most company policies?$q$,
$q$Drafting a press release$q$,
$q$Producing the final, unreviewed legal opinion on a merger$q$,
$q$Summarizing customer feedback themes$q$,
$q$Drafting internal training material$q$, 2,
$q$Professional opinions with legal effect require licensed human judgment and accountability; a model may assist research or drafting but cannot be the final authority.$q$);

select seed_q('CCAO-F', 'Workflow Integration and Solution Design', 'medium',
$q$When mapping a process to find where Claude helps most, the highest-value spots are usually:$q$,
$q$Steps requiring physical presence$q$,
$q$Reading, drafting, and reformatting steps that consume skilled time but follow describable patterns$q$,
$q$Steps that are already fully automated$q$,
$q$The steps nobody understands$q$, 2,
$q$Claude excels at language-heavy, pattern-describable work. Already-automated steps gain nothing, and poorly understood steps must be understood before they can be delegated.$q$);

select 'batch 1 done';

-- ============ Governance, Risk, and Responsible Use (10) ============

select seed_q('CCAO-F', 'Governance, Risk, and Responsible Use', 'easy',
$q$Before pasting a customer list with emails and order history into a Claude chat, an employee should first:$q$,
$q$Remove the header row$q$,
$q$Check company policy on sharing personal data with AI tools and minimize or anonymize what is shared$q$,
$q$Convert the file to PDF$q$,
$q$Split it into two smaller chats$q$, 2,
$q$Customer data is personal data. Policy check plus data minimization (share only what the task needs, anonymized where possible) is the required habit before any tool sees it.$q$);

select seed_q('CCAO-F', 'Governance, Risk, and Responsible Use', 'medium',
$q$A company wants assurance that its Claude workspace data is not used to train models. The right approach is to:$q$,
$q$Assume consumer and enterprise terms are identical$q$,
$q$Review the organization's actual plan terms and data controls, and configure the workspace accordingly$q$,
$q$Add "do not train on this" to every prompt$q$,
$q$Only use Claude on personal accounts$q$, 2,
$q$Data-use guarantees live in the organization's contractual terms and admin controls, not in prompt text. Governance means reading and configuring those, and they differ between consumer and enterprise offerings.$q$);

select seed_q('CCAO-F', 'Governance, Risk, and Responsible Use', 'medium',
$q$Which practice best reduces the risk of employees using unapproved AI tools ("shadow AI")?$q$,
$q$Blocking all AI tools at the firewall with no alternative$q$,
$q$Providing an approved, capable tool with clear usage guidelines and training$q$,
$q$Monitoring personal devices$q$,
$q$Annual reminders not to use AI$q$, 2,
$q$Shadow AI is driven by unmet demand. An approved tool with clear guidelines channels usage where it is governed; blanket bans push it underground.$q$);

select seed_q('CCAO-F', 'Governance, Risk, and Responsible Use', 'medium',
$q$A manager uses Claude to draft performance feedback. The key responsible-use consideration is:$q$,
$q$Feedback must rhyme with company values$q$,
$q$The manager stays accountable for the judgment; sensitive employee details shared should be minimal and policy-compliant$q$,
$q$Claude should decide the rating to remove bias$q$,
$q$Drafts must be at least a page long$q$, 2,
$q$People decisions require human accountability, and employee information is sensitive personal data — minimize what is shared and keep the judgment human.$q$);

select seed_q('CCAO-F', 'Governance, Risk, and Responsible Use', 'hard',
$q$A team automates first-draft credit-limit recommendations with Claude. Which governance control matters most?$q$,
$q$A catchy internal name for the tool$q$,
$q$Documented human decision authority, bias monitoring across customer groups, and an audit trail of recommendations vs decisions$q$,
$q$Running the tool only during business hours$q$,
$q$Keeping the prompt secret from the compliance team$q$, 2,
$q$Credit decisions are regulated and consequential: the control set is human decision rights, monitoring for disparate outcomes, and auditability. Compliance must see how it works, not be shielded from it.$q$);

select seed_q('CCAO-F', 'Governance, Risk, and Responsible Use', 'easy',
$q$Claude declines a request it interprets as harmful. The appropriate employee response is to:$q$,
$q$Rephrase the request to disguise its intent$q$,
$q$Consider whether the request is appropriate, and raise it through proper channels if it is legitimate$q$,
$q$Try another AI tool until one complies$q$,
$q$Report the refusal as a product bug$q$, 2,
$q$Refusals are a safety feature. Legitimate needs blocked in error go through proper channels; disguising intent to bypass safeguards violates responsible-use policy.$q$);

select seed_q('CCAO-F', 'Governance, Risk, and Responsible Use', 'medium',
$q$Publishing Claude-assisted external content (blog posts, whitepapers) responsibly means:$q$,
$q$Never disclosing any tool was used$q$,
$q$Human editorial review, fact-checking, and following the company's disclosure policy$q$,
$q$Publishing faster since drafting is cheap$q$,
$q$Watermarking every image$q$, 2,
$q$Responsibility for published content stays with the company: review and fact-check like any authored piece, and follow whatever disclosure standard the organization has set.$q$);

select seed_q('CCAO-F', 'Governance, Risk, and Responsible Use', 'medium',
$q$Which data category generally requires the MOST caution before use in any AI workflow?$q$,
$q$Published press releases$q$,
$q$Anonymized aggregate statistics$q$,
$q$Health, financial, or government-ID information about identifiable people$q$,
$q$Public product documentation$q$, 3,
$q$Special-category personal data (health, financial, IDs) carries the highest regulatory and harm risk and typically needs explicit legal basis and controls before any processing.$q$);

select seed_q('CCAO-F', 'Governance, Risk, and Responsible Use', 'medium',
$q$An AI usage policy is most effective when it:$q$,
$q$Lists banned tools only$q$,
$q$Gives concrete do/do-not examples for common tasks, names approved tools, and tells people where to ask$q$,
$q$Is written once and never revisited$q$,
$q$Requires sign-off for every individual prompt$q$, 2,
$q$Effective policy is actionable: concrete examples, approved tools, and a clear question channel. Pure ban lists and per-prompt bureaucracy both fail in practice, and policies must evolve with the tools.$q$);

select seed_q('CCAO-F', 'Governance, Risk, and Responsible Use', 'hard',
$q$A vendor demo shows Claude summarizing your customer contracts on the vendor's own account. What should procurement verify before the pilot?$q$,
$q$The vendor's font choices$q$,
$q$Where the data flows, whose account processes it, retention terms, and whether your data-protection agreements cover that processing$q$,
$q$Whether summaries are longer than the originals$q$,
$q$The vendor's office location only$q$, 2,
$q$Third-party processing of customer contracts triggers data-protection duties: know the data flow, the processing account, retention, and contractual coverage before real data is used.$q$);

-- ============ Prompting and Task Execution (10) ============

select seed_q('CCAO-F', 'Prompting and Task Execution', 'easy',
$q$Which prompt will most reliably get a usable first draft of a customer apology email?$q$,
$q$"Write an email."$q$,
$q$"Write a 150-word apology email to a customer whose delivery was late, in a warm professional tone, offering a 10% discount code."$q$,
$q$"Apologize."$q$,
$q$"You know what to do."$q$, 2,
$q$Specific instructions — audience, situation, length, tone, and required content — remove guesswork and produce a draft that needs less editing.$q$);

select seed_q('CCAO-F', 'Prompting and Task Execution', 'medium',
$q$A team keeps getting inconsistent formats from Claude for their weekly report. The most effective fix is to:$q$,
$q$Ask more politely$q$,
$q$Include an example of a correctly formatted report in the prompt and tell Claude to match it$q$,
$q$Use shorter sentences$q$,
$q$Run the prompt twice and merge$q$, 2,
$q$Showing an example (few-shot prompting) is the most reliable way to pin down format. Models imitate demonstrated structure far more consistently than described structure.$q$);

select seed_q('CCAO-F', 'Prompting and Task Execution', 'medium',
$q$Why does giving Claude a role ("You are a meticulous compliance reviewer…") improve results for review tasks?$q$,
$q$It unlocks hidden model features$q$,
$q$It frames the perspective, priorities, and standards the response should apply$q$,
$q$It makes responses shorter$q$,
$q$It disables safety filters$q$, 2,
$q$Role framing sets the point of view and quality bar the model applies — a meticulous reviewer surfaces issues a generic assistant may gloss over.$q$);

select seed_q('CCAO-F', 'Prompting and Task Execution', 'medium',
$q$For a complex request — "analyze this survey data, identify the top three themes, and draft an exec summary" — the best prompting strategy is to:$q$,
$q$Send it as one vague sentence$q$,
$q$Break the work into explicit steps in the prompt (or across turns) and review intermediate output$q$,
$q$Ask for the summary first, themes later$q$,
$q$Paste the data with no instructions$q$, 2,
$q$Decomposing multi-part work into ordered steps, with checkpoints on intermediate results, produces more reliable output than a single compressed ask.$q$);

select seed_q('CCAO-F', 'Prompting and Task Execution', 'easy',
$q$Claude's first draft is close but the tone is too formal. The efficient next step is to:$q$,
$q$Start a new chat and rewrite the entire prompt$q$,
$q$Reply with the correction: "Make it more conversational, keep everything else."$q$,
$q$Accept it — tone cannot be changed$q$,
$q$Fix the tone by hand every week$q$, 2,
$q$Iterative refinement in-conversation is the intended workflow: state what to change and what to keep, and the model revises the existing draft.$q$);

select seed_q('CCAO-F', 'Prompting and Task Execution', 'medium',
$q$What does providing context ("This is for our board, who are skeptical about the project") do in a prompt?$q$,
$q$Nothing — context is ignored$q$,
$q$Shapes content choices, emphasis, and tone toward the actual audience and situation$q$,
$q$Doubles the cost of the request$q$,
$q$Guarantees factual accuracy$q$, 2,
$q$Audience and situational context steer what the model emphasizes and how it frames it — a skeptical board gets evidence-forward, risk-aware framing.$q$);

select seed_q('CCAO-F', 'Prompting and Task Execution', 'hard',
$q$A prompt says: "Summarize the attached policy. Do not mention pricing." The output mentions pricing anyway. Which revision is most likely to fix it?$q$,
$q$Repeat "do not mention pricing" five times$q$,
$q$State the positive instruction — "Cover only eligibility, process, and timelines" — and put the constraint near the task description$q$,
$q$Switch to all caps$q$,
$q$Remove all instructions$q$, 2,
$q$Positive scope instructions ("cover only X, Y, Z") are followed more reliably than negations, and placing constraints close to the task keeps them salient.$q$);

select seed_q('CCAO-F', 'Prompting and Task Execution', 'medium',
$q$When asking Claude to work strictly from a provided document, the best instruction is:$q$,
$q$"Answer from the attached document only; if it does not contain the answer, say so."$q$,
$q$"Use everything you know."$q$,
$q$"Guess when unsure."$q$,
$q$"Answer in one word."$q$, 1,
$q$Grounding instructions confine answers to the provided source and give the model an explicit out ("say so") instead of filling gaps from general knowledge.$q$);

select seed_q('CCAO-F', 'Prompting and Task Execution', 'medium',
$q$A reusable prompt template for the team should contain:$q$,
$q$Yesterday's specific customer name hard-coded$q$,
$q$Fixed instructions plus clearly marked slots for the parts that change each use$q$,
$q$No instructions, to stay flexible$q$,
$q$At least 2,000 words$q$, 2,
$q$Templates separate the stable instruction scaffold from per-use variables, giving consistent results with minimal editing per run.$q$);

select seed_q('CCAO-F', 'Prompting and Task Execution', 'easy',
$q$Asking Claude to "think through the problem step by step before answering" tends to help most on:$q$,
$q$Copying text verbatim$q$,
$q$Multi-step reasoning tasks like reconciling numbers or weighing options$q$,
$q$Returning the current date$q$,
$q$Shortening a sentence$q$, 2,
$q$Step-by-step reasoning gives the model room to work through dependencies before committing to an answer — most valuable on multi-step analytical tasks.$q$);

select 'batch 2 done';

-- ============ Product and Model Selection (8) ============

select seed_q('CCAO-F', 'Product and Model Selection', 'medium',
$q$A team runs thousands of short, simple classification tasks daily and occasionally needs deep analysis of complex documents. The sensible model strategy is:$q$,
$q$The most capable model for everything$q$,
$q$A fast, economical model for the high-volume simple tasks and a more capable model for the complex analysis$q$,
$q$The cheapest model for everything$q$,
$q$Alternate models day by day$q$, 2,
$q$Model selection matches capability to task: fast economical models handle high-volume routine work; reserve the most capable (and costly) models for work that needs the depth.$q$);

select seed_q('CCAO-F', 'Product and Model Selection', 'easy',
$q$Within Anthropic's model family, the general tradeoff across tiers is:$q$,
$q$Larger models are always faster$q$,
$q$More capable tiers offer deeper reasoning at higher cost and latency; lighter tiers are faster and cheaper$q$,
$q$All tiers behave identically$q$,
$q$Lighter tiers refuse more requests$q$, 2,
$q$The family spans a capability/cost/speed spectrum: top tiers for hardest reasoning, light tiers for speed and volume — choose per task, not by habit.$q$);

select seed_q('CCAO-F', 'Product and Model Selection', 'medium',
$q$Claude Projects are most useful when a team wants to:$q$,
$q$Send one-off questions with no context$q$,
$q$Keep shared instructions and reference knowledge attached to an ongoing body of work$q$,
$q$Reduce their subscription cost$q$,
$q$Bypass workspace admin settings$q$, 2,
$q$A Project carries standing instructions and knowledge files, so every conversation in it starts with the right context — ideal for ongoing, team-shared work.$q$);

select seed_q('CCAO-F', 'Product and Model Selection', 'medium',
$q$Which situation calls for the Claude API rather than the claude.ai apps?$q$,
$q$A manager asking for help with one presentation$q$,
$q$Automatically processing every incoming support ticket through the company's own systems$q$,
$q$Brainstorming in a team meeting$q$,
$q$Reading a long PDF once$q$, 2,
$q$Programmatic, system-to-system processing at volume is API territory; the apps serve interactive human use.$q$);

select seed_q('CCAO-F', 'Product and Model Selection', 'easy',
$q$The "context window" of a model determines:$q$,
$q$How fast it types$q$,
$q$How much text (conversation plus documents) it can consider at once$q$,
$q$How many users can share an account$q$,
$q$Which languages it speaks$q$, 2,
$q$The context window is the model's working span — everything it must consider (instructions, history, documents) has to fit within it.$q$);

select seed_q('CCAO-F', 'Product and Model Selection', 'medium',
$q$A compliance-minded buyer comparing Claude plans for a 200-person rollout should weigh most heavily:$q$,
$q$The color scheme of the interface$q$,
$q$Admin controls, SSO, data-use terms, and audit capabilities of the enterprise offering$q$,
$q$Whether emoji render correctly$q$,
$q$The length of the marketing page$q$, 2,
$q$At organizational scale the differentiators are governance features: identity integration, admin controls, contractual data terms, and auditability.$q$);

select seed_q('CCAO-F', 'Product and Model Selection', 'medium',
$q$When would a team choose extended thinking (deeper reasoning) options for a task?$q$,
$q$For every request, as a default$q$,
$q$For genuinely hard problems — intricate analysis, tricky tradeoffs — where extra reasoning time is worth it$q$,
$q$Only for formatting fixes$q$,
$q$Never — speed always wins$q$, 2,
$q$Deeper reasoning modes trade latency (and cost) for quality on hard problems. Reserve them for tasks where that depth changes the outcome.$q$);

select seed_q('CCAO-F', 'Product and Model Selection', 'hard',
$q$A workflow needs Claude to use the company's live inventory system while chatting with staff. Which capability makes this possible?$q$,
$q$Larger context windows alone$q$,
$q$Connecting Claude to tools/data sources (e.g. via MCP connectors) so it can query live systems$q$,
$q$Uploading a screenshot of the inventory page daily$q$,
$q$Asking the model to remember stock levels$q$, 2,
$q$Live system access is what tool connections (like MCP-based connectors) provide. Context windows and memory hold static text, not current inventory truth.$q$);

-- ============ Configuration and Knowledge Management (8) ============

select seed_q('CCAO-F', 'Configuration and Knowledge Management', 'medium',
$q$What belongs in a Project's standing instructions rather than in each chat?$q$,
$q$Today's specific question$q$,
$q$Stable guidance: the team's voice, format standards, definitions, and what to always/never do$q$,
$q$Random trivia$q$,
$q$Another team's confidential data$q$, 2,
$q$Standing instructions hold what is true for every conversation — style, standards, definitions — so individual chats only add the task at hand.$q$);

select seed_q('CCAO-F', 'Configuration and Knowledge Management', 'medium',
$q$A team's project knowledge contains three outdated pricing sheets alongside the current one. The likely symptom is:$q$,
$q$Faster responses$q$,
$q$Answers that mix or cite superseded prices$q$,
$q$The project refusing to open$q$,
$q$Nothing — old files are ignored automatically$q$, 2,
$q$The model cannot know which document is authoritative unless told; conflicting knowledge produces mixed answers. Curating (removing or versioning) knowledge is part of configuration hygiene.$q$);

select seed_q('CCAO-F', 'Configuration and Knowledge Management', 'easy',
$q$The main benefit of maintaining a shared prompt library for common team tasks is:$q$,
$q$It reduces the electricity bill$q$,
$q$Consistent, quality-controlled results that new team members can reuse immediately$q$,
$q$It prevents anyone from writing new prompts$q$,
$q$It makes chats private$q$, 2,
$q$A curated library captures what works, standardizes quality, and shortens onboarding — prompt knowledge becomes a team asset instead of individual folklore.$q$);

select seed_q('CCAO-F', 'Configuration and Knowledge Management', 'medium',
$q$Who should be able to edit the standing instructions of a team's shared Project?$q$,
$q$Everyone in the company$q$,
$q$Designated owners, with changes communicated to the team$q$,
$q$Nobody, ever$q$,
$q$An external consultant only$q$, 2,
$q$Standing instructions shape every response the team gets; ownership plus change communication keeps them deliberate and traceable without freezing improvement.$q$);

select seed_q('CCAO-F', 'Configuration and Knowledge Management', 'medium',
$q$When Claude's answer must reflect this quarter's org chart, the knowledge management rule is:$q$,
$q$Upload the org chart once and never touch it$q$,
$q$Keep a single current version in the knowledge source and replace it each quarter$q$,
$q$Paste the org chart into every message$q$,
$q$Trust the model's general knowledge of your company$q$, 2,
$q$Time-sensitive facts need one authoritative, refreshed source. The model has no knowledge of your internal org beyond what you maintain for it.$q$);

select seed_q('CCAO-F', 'Configuration and Knowledge Management', 'hard',
$q$A 300-page manual uploaded to a Project is producing shallow answers about a niche procedure. A practical fix is to:$q$,
$q$Upload the manual five more times$q$,
$q$Extract the key procedure into a focused reference document that sits alongside (or replaces) the giant manual$q$,
$q$Ask questions in all caps$q$,
$q$Delete all knowledge and rely on memory$q$, 2,
$q$Focused, well-structured references get used more reliably than one enormous document; distilling the high-value content improves retrieval and answer depth.$q$);

select seed_q('CCAO-F', 'Configuration and Knowledge Management', 'easy',
$q$Custom instructions that say "Always answer in our brand voice: plain, friendly, no jargon" are an example of:$q$,
$q$A security vulnerability$q$,
$q$Configuration that standardizes output across every conversation$q$,
$q$A one-off prompt$q$,
$q$A model training run$q$, 2,
$q$Standing custom instructions are configuration: they apply automatically, giving consistent voice without repeating the requirement in each chat. They do not retrain the model.$q$);

select seed_q('CCAO-F', 'Configuration and Knowledge Management', 'medium',
$q$What is the right cadence for reviewing a team's Claude configuration (instructions, knowledge, templates)?$q$,
$q$Never — set and forget$q$,
$q$On a regular schedule and after major changes to products, policy, or team structure$q$,
$q$Every hour$q$,
$q$Only when something breaks publicly$q$, 2,
$q$Configuration mirrors the business; scheduled reviews plus event-driven updates keep it current before stale guidance causes visible errors.$q$);

-- ============ Troubleshooting and Optimization (7) ============

select seed_q('CCAO-F', 'Troubleshooting and Optimization', 'medium',
$q$Halfway through a very long chat with many pasted documents, Claude starts losing track of earlier details. The practical remedy is to:$q$,
$q$Type faster$q$,
$q$Start a fresh conversation with a concise summary and only the documents that still matter$q$,
$q$Paste all documents again$q$,
$q$Switch to a different browser$q$, 2,
$q$Long conversations accumulate context until earlier details compete for attention or fall away. Restarting with a distilled summary restores a clean, relevant working set.$q$);

select seed_q('CCAO-F', 'Troubleshooting and Optimization', 'easy',
$q$Claude's answer stops mid-sentence. The simplest first fix is to:$q$,
$q$Report an outage$q$,
$q$Ask it to continue from where it stopped$q$,
$q$Restart the computer$q$,
$q$Rewrite the whole prompt$q$, 2,
$q$Responses have length limits; "continue" resumes the output. Escalate only if that fails.$q$);

select seed_q('CCAO-F', 'Troubleshooting and Optimization', 'medium',
$q$Claude gives a generic answer about "your return policy" instead of the company's actual policy. The most likely cause is:$q$,
$q$The model is broken$q$,
$q$The actual policy was never provided — it is answering from general knowledge$q$,
$q$The question was too polite$q$,
$q$The chat is too short$q$, 2,
$q$Company-specific answers require company-specific input (pasted, uploaded, or in project knowledge). Without it the model can only generalize.$q$);

select seed_q('CCAO-F', 'Troubleshooting and Optimization', 'medium',
$q$A prompt that worked well for months now produces weaker results on a new category of input. The disciplined first step is to:$q$,
$q$Rewrite everything from scratch immediately$q$,
$q$Collect failing examples, identify what changed in the inputs, and test prompt adjustments against those cases$q$,
$q$Blame the newest team member$q$,
$q$Stop using the workflow permanently$q$, 2,
$q$Diagnose before treating: failing examples reveal the pattern (new input type), and candidate fixes are validated against exactly those cases.$q$);

select seed_q('CCAO-F', 'Troubleshooting and Optimization', 'hard',
$q$A weekly Claude workflow takes 40 manual minutes of copy-pasting between systems. The best optimization mindset is:$q$,
$q$Accept it — copy-paste is unavoidable$q$,
$q$Look for integration options (connected tools, uploads, or automation) to remove the manual transfer, weighing effort against time saved$q$,
$q$Do the task less often$q$,
$q$Add more people to paste faster$q$, 2,
$q$Recurring manual glue work is an integration signal. Evaluate connections or automation with a simple effort-vs-benefit lens instead of scaling the manual step.$q$);

select seed_q('CCAO-F', 'Troubleshooting and Optimization', 'medium',
$q$Claude keeps including a section the team always deletes from its weekly draft. The right fix is to:$q$,
$q$Keep deleting it by hand forever$q$,
$q$Update the standing instructions/template to exclude that section explicitly$q$,
$q$Ask a different colleague to delete it$q$,
$q$Shorten the entire report$q$, 2,
$q$Recurring manual corrections are configuration feedback: fold the correction into the standing instructions once, and every future draft improves.$q$);

select seed_q('CCAO-F', 'Troubleshooting and Optimization', 'easy',
$q$When a Claude response misunderstands the request, the fastest path to a good answer is usually to:$q$,
$q$Clarify what you meant and point at the misunderstanding directly$q$,
$q$Send the identical prompt again$q$,
$q$Open a support ticket$q$,
$q$Give up on the task$q$, 1,
$q$Models respond well to explicit correction; naming the misunderstanding redirects the next attempt. Resending unchanged input mostly reproduces the miss.$q$);

select 'batch 3 done';

drop function seed_q(text, text, text, text, text, text, text, text, int, text);

-- Shuffle option positions per seeded question (deterministic via setseed) so the
-- correct answer's position/color varies — otherwise players learn the pattern.
-- Delete-and-reinsert per question sidesteps the unique/check constraints (safe:
-- no answers reference seed options at seed time).
do $shuffle$
declare
    q record;
    o record;
    pos int;
begin
    perform setseed(0.42);
    for q in select id from questions where source = 'claude-generated 2026-08' loop
        pos := 0;
        for o in
            select option_text, is_correct
            from question_options
            where question_id = q.id
            order by random()
        loop
            pos := pos + 1;
            if pos = 1 then
                delete from question_options where question_id = q.id;
            end if;
            insert into question_options (question_id, option_text, is_correct, display_order)
            values (q.id, o.option_text, o.is_correct, pos);
        end loop;
    end loop;
end;
$shuffle$;
